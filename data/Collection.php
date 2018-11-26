<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use Closure;

/**
 * The `Collection` class extends the generic `lithium\util\Collection` class to provide
 * context-specific features for working with sets of data persisted by a backend data store. This
 * is a general abstraction that operates on arbitrary sets of data from either relational or
 * non-relational data stores.
 */
abstract class Collection extends \lithium\util\Collection {

	/**
	 * A reference to this object's parent `Document` object.
	 *
	 * @var object
	 */
	protected $_parent = null;

	/**
	 * If this `Collection` instance has a parent document (see `$_parent`), this value indicates
	 * the key name of the parent document that contains it.
	 *
	 * @see lithium\data\Collection::$_parent
	 * @var string
	 */
	protected $_pathKey = null;

	/**
	 * The fully-namespaced class name of the model object to which this entity set is bound. This
	 * is usually the model that executed the query which created this object.
	 *
	 * @var string
	 */
	protected $_model = null;

	/**
	 * A reference to the query object that originated this entity set; usually an instance of
	 * `lithium\data\model\Query`.
	 *
	 * @see lithium\data\model\Query
	 * @var object
	 */
	protected $_query = null;

	/**
	 * A pointer or resource that is used to load entities from the backend data source that
	 * originated this collection.
	 *
	 * @var resource
	 */
	protected $_result = null;

	/**
	 * Indicates whether the current position is valid or not. This overrides the default value of
	 * the parent class.
	 *
	 * @var boolean
	 * @see lithium\util\Collection::valid()
	 */
	protected $_valid = true;

	/**
	 * Contains an array of backend-specific statistics generated by the query that produced this
	 * `Collection` object. These stats are accessible via the `stats()` method.
	 *
	 * @see lithium\data\Collection::stats()
	 * @var array
	 */
	protected $_stats = array();

	/**
	 * Setted to `true` when the collection has begun iterating.
	 *
	 * @var integer
	 */
	protected $_started = false;

	/**
	 * Indicates whether this array was part of a document loaded from a data source, or is part of
	 * a new document, or is in newly-added field of an existing document.
	 *
	 * @var boolean
	 */
	protected $_exists = false;

	/**
	 * If the `Collection` has a schema object assigned (rather than loading one from a model), it
	 * will be assigned here.
	 *
	 * @see lithium\data\Schema
	 * @var lithium\data\Schema
	 */
	protected $_schema = null;

	/**
	 * Hold the "data export" handlers where the keys are fully-namespaced class
	 * names, and the values are closures that take an instance of the class as a
	 * parameter, and return an array or scalar value that the instance represents.
	 *
	 * @see lithium\data\Collection::to()
	 * @var array
	 */
	protected $_handlers = array();

	/**
	 * Holds an array of values that should be processed on initialization.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'model', 'result', 'query', 'parent', 'stats', 'pathKey', 'exists', 'schema', 'handlers'
	);

	/**
	 * Class constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array('data' => array(), 'model' => null);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		$data = $this->_config['data'];
		parent::_init();
		$this->set($data);
		foreach (array('classes', 'model', 'result', 'query') as $key) {
			unset($this->_config[$key]);
		}
	}

	/**
	 * Configures protected properties of a `Collection` so that it is parented to `$parent`.
	 *
	 * @param object $parent
	 * @param array $config
	 * @return void
	 */
	public function assignTo($parent, array $config = array()) {
		foreach ($config as $key => $val) {
			$this->{'_' . $key} = $val;
		}
		$this->_parent =& $parent;
	}

	/**
	 * Returns the model which this particular collection is based off of.
	 *
	 * @return string The fully qualified model class name.
	 */
	public function model() {
		return $this->_model;
	}

	/**
	 * Returns the object's parent `Document` object.
	 *
	 * @return object
	 */
	public function parent() {
		return $this->_parent;
	}

	/**
	 * A flag indicating whether or not the items of this collection exists.
	 *
	 * @return boolean `True` if exists, `false` otherwise.
	 */
	public function exists() {
		return $this->_exists;
	}

	public function schema($field = null) {
		$schema = null;

		switch (true) {
			case ($this->_schema):
				$schema = $this->_schema;
			break;
			case ($model = $this->_model):
				$schema = $model::schema();
			break;
		}
		if ($schema) {
			return $field ? $schema->fields($field) : $schema;
		}
	}

	/**
	 * Allows several properties to be assigned at once.
	 *
	 * For example:
	 * {{{
	 * $collection->set(array('title' => 'Lorem Ipsum', 'value' => 42));
	 * }}}
	 *
	 * @param $values An associative array of fields and values to assign to the `Collection`.
	 * @return void
	 */
	public function set($values) {
		foreach ($values as $key => $val) {
			$this[$key] = $val;
		}
	}

	/**
	 * Returns a boolean indicating whether an offset exists for the
	 * current `Collection`.
	 *
	 * @param string $offset String or integer indicating the offset or
	 *        index of an entity in the set.
	 * @return boolean Result.
	 */
	public function offsetExists($offset) {
		$this->offsetGet($offset);
		return \array_key_exists($offset, $this->_data);
	}

	/**
	 * Gets an `Entity` object using PHP's array syntax, i.e. `$documents[3]` or `$records[5]`.
	 *
	 * @param mixed $offset The offset.
	 * @return mixed Returns an `Entity` object if exists otherwise returns `null`.
	 */
	public function offsetGet($offset) {
		while (!\array_key_exists($offset, $this->_data) && $this->_populate()) {}

		if (\array_key_exists($offset, $this->_data)) {
			return $this->_data[$offset];
		}
		return null;
	}

	/**
	 * Adds the specified object to the `Collection` instance, and assigns associated metadata to
	 * the added object.
	 *
	 * @param string $offset The offset to assign the value to.
	 * @param mixed $data The entity object to add.
	 * @return mixed Returns the set `Entity` object.
	 */
	public function offsetSet($offset, $data) {
		$this->offsetGet($offset);
		return $this->_set($data, $offset);
	}

	/**
	 * Unsets an offset.
	 *
	 * @param integer $offset The offset to unset.
	 */
	public function offsetUnset($offset) {
		$this->offsetGet($offset);
		\prev($this->_data);
		if (\key($this->_data) === null) {
			$this->rewind();
		}
		unset($this->_data[$offset]);
	}

	/**
	 * Rewinds the collection to the beginning.
	 */
	public function rewind() {
		$this->_started = true;
		\reset($this->_data);
		$this->_valid = !empty($this->_data) || !\is_null($this->_populate());
		return \current($this->_data);
	}

	/**
	 * Returns the currently pointed to record's unique key.
	 *
	 * @param boolean $full If true, returns the complete key.
	 * @return mixed
	 */
	public function key($full = false) {
		if ($this->_started === false) {
			$this->current();
		}
		if ($this->_valid) {
			$key = \key($this->_data);
			return (\is_array($key) && !$full) ? \reset($key) : $key;
		}
		return null;
	}

	/**
	 * Returns the item keys.
	 *
	 * @return array The keys of the items.
	 */
	public function keys() {
		$this->offsetGet(null);
		return parent::keys();
	}

	/**
	 * Returns the currently pointed to record in the set.
	 *
	 * @return object `Record`
	 */
	public function current() {
		if (!$this->_started) {
			$this->rewind();
		}
		if (!$this->_valid) {
			return false;
		}
		return \current($this->_data);
	}

	/**
	 * Returns the next document in the set, and advances the object's internal pointer. If the end
	 * of the set is reached, a new document will be fetched from the data source connection handle
	 * If no more documents can be fetched, returns `null`.
	 *
	 * @return mixed Returns the next document in the set, or `false`, if no more documents are
	 *         available.
	 */
	public function next($self = null, $params = null, $chain = null) {
		if (!$this->_started) {
			$this->rewind();
		}
		\next($this->_data);
		$this->_valid = !(\key($this->_data) === null);
		if (!$this->_valid) {
			$this->_valid = !\is_null($this->_populate());
		}
		return \current($this->_data);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid() {
		if (!$this->_started) {
			$this->rewind();
		}
		return $this->_valid;
	}

	/**
	 * Overrides parent `find()` implementation to enable key/value-based filtering of entity
	 * objects contained in this collection.
	 *
	 * @param mixed $filter Callback to use for filtering, or array of key/value pairs which entity
	 *        properties will be matched against.
	 * @param array $options Options to modify the behavior of this method. See the documentation
	 *        for the `$options` parameter of `lithium\util\Collection::find()`.
	 * @return mixed The filtered items. Will be an array unless `'collect'` is defined in the
	 *         `$options` argument, then an instance of this class will be returned.
	 */
	public function find($filter, array $options = array()) {
		$this->offsetGet(null);
		if (\is_array($filter)) {
			$filter = $this->_filterFromArray($filter);
		}
		return parent::find($filter, $options);
	}

	/**
	 * Overrides parent `first()` implementation to enable key/value-based filtering.
	 *
	 * @param mixed $filter In addition to a callback (see parent), can also be an array where the
	 *              keys and values must match the property values of the objects being inspected.
	 * @return object Returns the first object found matching the filter criteria.
	 */
	public function first($filter = null) {
		return parent::first(\is_array($filter) ? $this->_filterFromArray($filter) : $filter);
	}

	/**
	 * Creates a filter based on an array of key/value pairs that must match the items in a
	 * `Collection`.
	 *
	 * @param array $filter An array of key/value pairs used to filter `Collection` items.
	 * @return Closure Returns a closure that wraps the array and attempts to match each value
	 *         against `Collection` item properties.
	 */
	protected function _filterFromArray(array $filter) {
		return function($item) use ($filter) {
			foreach ($filter as $key => $val) {
				if ($item->{$key} != $val) {
					return false;
				}
			}
			return true;
		};
	}

	/**
	 * Returns meta information for this `Collection`.
	 *
	 * @return array
	 */
	public function meta() {
		return array('model' => $this->_model);
	}

	/**
	 * Applies a callback to all data in the collection.
	 *
	 * Overridden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @return object This collection instance.
	 */
	public function each($filter) {
		$this->offsetGet(null);
		return parent::each($filter);
	}

	/**
	 * Applies a callback to a copy of all data in the collection
	 * and returns the result.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @param array $options The available options are:
	 *        - `'collect'`: If `true`, the results will be returned wrapped
	 *        in a new `Collection` object or subclass.
	 * @return object The filtered data.
	 */
	public function map($filter, array $options = array()) {
		$defaults = array('collect' => true);
		$options += $defaults;

		$this->offsetGet(null);
		$data = parent::map($filter, $options);

		if ($options['collect']) {
			foreach (array('_model', '_schema', '_pathKey') as $key) {
				$data->{$key} = $this->{$key};
			}
		}
		return $data;
	}

	/**
	 * Reduce, or fold, a collection down to a single value
	 *
	 * Overridden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @param mixed $initial Initial value
	 * @return mixed A single reduced value
	 */
	public function reduce($filter, $initial = false) {
		if (!$this->closed()) {
			while ($this->next()) {}
		}
		return parent::reduce($filter);
	}

	/**
	 * Sorts the objects in the collection, useful in situations where
	 * you are already using the underlying datastore to sort results.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param mixed $field The field to sort the data on, can also be a callback
	 *        to a custom sort function.
	 * @param array $options The available options are:
	 *        - No options yet implemented
	 * @return $this, useful for chaining this with other methods.
	 */
	public function sort($field = 'id', array $options = array()) {
		$this->offsetGet(null);

		if (\is_string($field)) {
			$sorter = function ($a, $b) use ($field) {
				if (\is_array($a)) {
					$a = (object) $a;
				}

				if (\is_array($b)) {
					$b = (object) $b;
				}

				return \strcmp($a->$field, $b->$field);
			};
		} elseif (\is_callable($field)) {
			$sorter = $field;
		}

		return parent::sort($sorter, $options);
	}

	/**
	 * Converts the current state of the data structure to an array.
	 *
	 * @return array Returns the array value of the data in this `Collection`.
	 */
	public function data() {
		return $this->to('array', array('indexed' => null));
	}

	/**
	 * Converts a `Collection` object to another type of object, or a simple type such as an array.
	 * The supported values of `$format` depend on the format handlers registered in the static
	 * property `Collection::$_formats`. The `Collection` class comes with built-in support for
	 * array conversion, but other formats may be registered.
	 *
	 * Once the appropriate handlers are registered, a `Collection` instance can be converted into
	 * any handler-supported format, i.e.: {{{
	 * $collection->to('json'); // returns a JSON string
	 * $collection->to('xml'); // returns an XML string
	 * }}}
	 *
	 *  _Please note that Lithium does not ship with a default XML handler, but one can be
	 * configured easily._
	 *
	 * @see lithium\util\Collection::formats()
	 * @see lithium\util\Collection::$_formats
	 * @param string $format By default the only supported value is `'array'`. However, additional
	 *        format handlers can be registered using the `formats()` method.
	 * @param array $options Options for converting this collection:
	 *        - `'internal'` _boolean_: Indicates whether the current internal representation of the
	 *          collection should be exported. Defaults to `false`, which uses the standard iterator
	 *          interfaces. This is useful for exporting record sets, where records are lazy-loaded,
	 *          and the collection must be iterated in order to fetch all objects.
	 * @return mixed The object converted to the value specified in `$format`; usually an array or
	 *         string.
	 */
	public function to($format, array $options = array()) {
		$defaults = array('internal' => false, 'indexed' => true, 'handlers' => array());
		$options += $defaults;

		$options['handlers'] += $this->_handlers;
		$this->offsetGet(null);

		$index = $options['indexed'] || ($options['indexed'] === null && $this->_parent === null);
		if (!$index) {
			$data = \array_values($this->_data);
		} else {
			$data = $options['internal'] ? $this->_data : $this;
		}
		return $this->_to($format, $data, $options);
	}

	/**
	 * Return's the pointer or resource that is used to load entities from the backend
	 * data source that originated this collection. This is useful in many cases for
	 * additional methods related to debugging queries.
	 *
	 * @return object The pointer or resource from the data source
	 */
	public function result() {
		return $this->_result;
	}

	/**
	 * Gets the stat or stats associated with this `Collection`.
	 *
	 * @param string $name Stat name.
	 * @return mixed Single stat if `$name` supplied, else all stats for this
	 *         `Collection`.
	 */
	public function stats($name = null) {
		if ($name) {
			return isset($this->_stats[$name]) ? $this->_stats[$name] : null;
		}
		return $this->_stats;
	}

	/**
	 * Executes when the associated result resource pointer reaches the end of its data set. The
	 * resource is freed by the connection, and the reference to the connection is unlinked.
	 */
	public function close() {
		if (!empty($this->_result)) {
			unset($this->_result);
			$this->_result = null;
		}
	}

	/**
	 * Checks to see if this entity has already fetched all available entities and freed the
	 * associated result resource.
	 *
	 * @return boolean Returns true if all entities are loaded and the database resources have been
	 *         freed, otherwise returns false.
	 */
	public function closed() {
		return empty($this->_result);
	}

	/**
	 * Ensures that the data set's connection is closed when the object is destroyed.
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * A method to be implemented by concrete `Collection` classes which, provided a reference to a
	 * backend data source, and a resource representing a query result cursor, fetches new result
	 * data and wraps it in the appropriate object type, which is added into the `Collection` and
	 * returned.
	 *
	 * @return mixed Returns the next `Record`, `Document` object or other `Entity` object if
	 *         exists. Returns `null` otherwise.
	 */
	abstract protected function _populate();

	/**
	 * A method to be implemented by concrete `Collection` classes which sets data to a specified
	 * offset and wraps all data array in its appropriate object type.
	 *
	 * @see lithium\data\Collection::_populate()
	 * @see lithium\data\Collection::_offsetSet()
	 * @param mixed $data An array or an `Entity` object to set.
	 * @param mixed $offset The offset. If offset is `null` data is simply appended to the set.
	 * @param array $options Any additional options to pass to the `Entity`'s constructor.
	 * @return object Returns the inserted `Record`, `Document` object or other `Entity` object.
	 */
	abstract protected function _set($data = null, $offset = null, $options = array());
}

?>
