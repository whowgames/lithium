<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongodb_new;

use MongoDB\BSON\ObjectId;

/**
 * Mock `MongoDB\Database` (and, through `__get()`, `MongoDB\Collection`) for `MongoDBNew`.
 * Adds the `mongodb/mongodb` write methods and `selectGridFSBucket()` to the legacy mock.
 *
 * `selectGridFSBucket()` records the bucket name in `$gridFsPrefix` and returns one
 * `MockGridFSBucket` per bucket name, kept in `$buckets`.
 */
class MockMongoConnection extends \lithium\tests\mocks\data\source\MockMongoConnection {

	public $buckets = array();

	public function insertOne($document, array $options = array()) {
		$id = ((array) $document)['_id'] ?? new ObjectId();
		$this->_record(__FUNCTION__, \compact('document', 'options'));

		return new class($id) {
			protected $_id;

			public function __construct($id) {
				$this->_id = $id;
			}

			public function getInsertedId() {
				return $this->_id;
			}
		};
	}

	public function updateOne($filter, $update, array $options = array()) {
		return $this->_record(__FUNCTION__, \compact('filter', 'update', 'options')) ?? true;
	}

	public function updateMany($filter, $update, array $options = array()) {
		return $this->_record(__FUNCTION__, \compact('filter', 'update', 'options')) ?? true;
	}

	public function deleteOne($filter, array $options = array()) {
		return $this->_record(__FUNCTION__, \compact('filter', 'options')) ?? true;
	}

	public function deleteMany($filter, array $options = array()) {
		return $this->_record(__FUNCTION__, \compact('filter', 'options')) ?? true;
	}

	public function find($filter = array(), $options = array()) {
		return $this->_record(__FUNCTION__, \compact('filter', 'options'))
			?? new MockResult();
	}

	public function selectGridFSBucket(array $options = array()) {
		$name = $options['bucketName'] ?? 'fs';
		$this->gridFsPrefix = $name;
		return $this->buckets[$name] ??= new MockGridFSBucket($name);
	}
}

?>