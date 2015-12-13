<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use lithium\data\model\Query;
use stdClass;
use lithium\util\Inflector;
use lithium\data\Model;
use lithium\data\Schema;
use lithium\data\Connections;
use lithium\tests\mocks\data\MockTag;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\tests\mocks\data\MockTagging;
use lithium\tests\mocks\data\MockCreator;
use lithium\tests\mocks\data\MockPostForValidates;
use lithium\tests\mocks\data\MockProduct;
use lithium\tests\mocks\data\MockSubProduct;
use lithium\tests\mocks\data\MockBadConnection;
use lithium\tests\mocks\core\MockCallable;
use lithium\tests\mocks\data\MockSource;
use lithium\tests\mocks\data\model\MockDatabase;

class ModelTest extends \lithium\test\Unit {

	protected $_altSchema = null;

	public function setUp() {
		Connections::add('mocksource', array('object' => new MockSource()));
		Connections::add('mockconn', array('object' => new MockDatabase()));

		MockPost::config(array('meta' => array('connection' => 'mocksource')));
		MockTag::config(array('meta' => array('connection' => 'mocksource')));
		MockComment::config(array('meta' => array('connection' => 'mocksource')));
		MockCreator::config(array('meta' => array('connection' => 'mocksource')));

		MockSubProduct::config(array('meta' => array('connection' => 'mockconn')));
		MockProduct::config(array('meta' => array('connection' => 'mockconn')));
		MockPostForValidates::config(array('meta' => array('connection' => 'mockconn')));

		$this->_altSchema = new Schema(array(
			'fields' => array(
				'id' => array('type' => 'integer'),
				'author_id' => array('type' => 'integer'),
				'title' => array('type' => 'string'),
				'body' => array('type' => 'text')
			)
		));
	}

	public function tearDown() {
		Connections::remove('mocksource');
		Connections::remove('mockconn');
		MockPost::reset();
		MockTag::reset();
		MockComment::reset();
		MockCreator::reset();
		MockSubProduct::reset();
		MockProduct::reset();
		MockPostForValidates::reset();
	}

	public function testOverrideMeta() {
		MockTag::reset();
		MockTag::meta(array('id' => 'key'));
		$meta = MockTag::meta();
		$this->assertFalse($meta['connection']);
		$this->assertEqual('mock_tags', $meta['source']);
		$this->assertEqual('key', $meta['id']);
	}

	public function testClassInitialization() {
		$expected = MockPost::instances();
		MockPost::config();
		$this->assertEqual($expected, MockPost::instances());
		Model::config();
		$this->assertEqual($expected, MockPost::instances());

		$this->assertEqual('mock_posts', MockPost::meta('source'));

		MockPost::config(array('meta' => array('source' => 'post')));
		$this->assertEqual('post', MockPost::meta('source'));

		MockPost::config(array('meta' => array('source' => false)));
		$this->assertFalse(MockPost::meta('source'));

		MockPost::config(array('meta' => array('source' => null)));
		$this->assertIdentical('mock_posts', MockPost::meta('source'));

		MockPost::config();
		$this->assertEqual('mock_posts', MockPost::meta('source'));
		$this->assertEqual('mocksource', MockPost::meta('connection'));

		MockPost::config(array('meta' => array('source' => 'toreset')));
		MockPost::reset();
		MockPost::config(array('meta' => array('connection' => 'mocksource')));
		$this->assertEqual('mock_posts', MockPost::meta('source'));
		$this->assertEqual('mocksource', MockPost::meta('connection'));

		MockPost::config(array('query' => array('with' => array('MockComment'), 'limit' => 10)));
		$expected =  array(
			'with' => array('MockComment'),
			'limit' => 10,
			'conditions' => null,
			'fields' => null,
			'order' => null,
			'page' => null
		);
		$this->assertEqual($expected, MockPost::query());

		$finder = array(
			'fields' => array('title', 'body')
		);
		MockPost::finder('myFinder', $finder);
		$result = MockPost::find('myFinder');

		$expected = $finder + array(
			'order' => null,
			'limit' => 10,
			'conditions' => null,
			'page' => null,
			'with' => array('MockComment'),
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\MockPost'
		);
		$this->assertEqual($expected, $result['options']);

		$finder = array(
			'fields' => array('id', 'title')
		);
		MockPost::reset();
		MockPost::config(array('meta' => array('connection' => 'mocksource')));
		$result = MockPost::finder('myFinder');
		$this->assertNull($result);
	}

	public function testInstanceMethods() {
		MockPost::instanceMethods(array());
		$methods = MockPost::instanceMethods();
		$this->assertEmpty($methods);

		MockPost::instanceMethods(array(
			'first' => array(
				'lithium\tests\mocks\data\source\MockMongoPost',
				'testInstanceMethods'
			),
			'second' => function($entity) {}
		));

		$methods = MockPost::instanceMethods();
		$this->assertCount(2, $methods);

		MockPost::instanceMethods(array(
			'third' => function($entity) {}
		));

		$methods = MockPost::instanceMethods();
		$this->assertCount(3, $methods);
	}

	public function testMetaInformation() {
		$class = 'lithium\tests\mocks\data\MockPost';
		$expected = compact('class') + array(
			'name'        => 'MockPost',
			'key'         => 'id',
			'title'       => 'title',
			'source'      => 'mock_posts',
			'connection'  => 'mocksource',
			'locked'      => true
		);

		$this->assertEqual($expected, MockPost::meta());

		$class = 'lithium\tests\mocks\data\MockComment';
		$expected = compact('class') + array(
			'name'        => 'MockComment',
			'key'         => 'comment_id',
			'title'       => 'comment_id',
			'source'      => 'mock_comments',
			'connection'  => 'mocksource',
			'locked'      => true
		);
		$this->assertEqual($expected, MockComment::meta());

		$expected += array('foo' => 'bar');
		MockComment::meta('foo', 'bar');
		$this->assertEqual($expected, MockComment::meta());

		$expected += array('bar' => true, 'baz' => false);
		MockComment::meta(array('bar' => true, 'baz' => false));
		$this->assertEqual($expected, MockComment::meta());
	}

	public function testSchemaLoading() {
		$result = MockPost::schema();
		$this->assertNotEmpty($result);
		$this->assertEqual($result->fields(), MockPost::schema()->fields());

		MockPost::config(array('schema' => $this->_altSchema));
		$this->assertEqual($this->_altSchema->fields(), MockPost::schema()->fields());
	}

	public function testSchemaInheritance() {
		$result = MockSubProduct::schema();
		$this->assertTrue(array_key_exists('price', $result->fields()));
	}

	public function testInitializationInheritance() {
		$meta = array (
			'name'       => 'MockSubProduct',
			'source'     => 'mock_products',
			'title'      => 'name',
			'class'      => 'lithium\tests\mocks\data\MockSubProduct',
			'connection' => 'mockconn',
			'key'        => 'id',
			'locked'     => true
		);
		$this->assertEqual($meta, MockSubProduct::meta());

		$this->assertArrayHasKey('MockCreator', MockSubProduct::relations());

		$this->assertCount(4, MockSubProduct::finders());

		$this->assertCount(1, MockSubProduct::initializers());

		$config = array('query' => array('with' => array('MockCreator')));
		MockProduct::config(compact('config'));
		$this->assertEqual(MockProduct::query(), MockSubProduct::query());

		$expected = array('limit' => 50) + MockProduct::query();
		MockSubProduct::config(array('query' => $expected));
		$this->assertEqual($expected, MockSubProduct::query());

		MockPostForValidates::config(array(
			'classes' => array('connections' => 'lithium\tests\mocks\data\MockConnections'),
			'meta' => array('connection' => new MockCallable())
		));
		$conn = MockPostForValidates::connection();

		$this->assertInstanceOf('lithium\tests\mocks\core\MockCallable', $conn);
	}

	public function testCustomAttributesInheritance() {
		$expected = array(
			'prop1' => 'value1',
			'prop2' => 'value2'
		);
		$result = MockSubProduct::attribute('_custom');
		$this->assertEqual($expected, $result);
	}

	public function testAttributesInheritanceWithObject() {
		$expected = array(
			'id' => array('type' => 'id'),
			'title' => array('type' => 'string', 'null' => false),
			'body' => array('type' => 'text', 'null' => false)
		);
		$schema = new Schema(array('fields' => $expected));

		MockSubProduct::config(compact('schema'));
		$result = MockSubProduct::schema();
		$this->assertEqual($expected, $result->fields());
	}

	public function testFieldIntrospection() {
		$this->assertNotEmpty(MockComment::hasField('comment_id'));
		$this->assertEmpty(MockComment::hasField('foo'));
		$this->assertEqual('comment_id', MockComment::hasField(array('comment_id')));
	}

	/**
	 * Tests introspecting the relationship settings for the model as a whole, various relationship
	 * types, and individual relationships.
	 *
	 * @todo Some tests will need to change when full relationship support is built out.
	 */
	public function testRelationshipIntrospection() {
		$result = array_keys(MockPost::relations());
		$expected = array('MockComment');
		$this->assertEqual($expected, $result);

		$result = MockPost::relations('hasMany');
		$this->assertEqual($expected, $result);

		$result = array_keys(MockComment::relations());
		$expected = array('MockPost');
		$this->assertEqual($expected, $result);

		$result = MockComment::relations('belongsTo');
		$this->assertEqual($expected, $result);

		$this->assertEmpty(MockComment::relations('hasMany'));
		$this->assertEmpty(MockPost::relations('belongsTo'));

		$expected = array(
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'key' => array('mock_post_id' => 'id'),
			'from' => 'lithium\tests\mocks\data\MockComment',
			'to' => 'lithium\tests\mocks\data\MockPost',
			'link' => 'key',
			'fields' => true,
			'fieldName' => 'mock_post',
			'constraints' => array(),
			'strategy' => null,
			'init' => true
		);
		$this->assertEqual($expected, MockComment::relations('MockPost')->data());

		$expected = array(
			'name' => 'MockComment',
			'type' => 'hasMany',
			'from' => 'lithium\tests\mocks\data\MockPost',
			'to' => 'lithium\tests\mocks\data\MockComment',
			'fields' => true,
			'key' => array('id' => 'mock_post_id'),
			'link' => 'key',
			'fieldName' => 'mock_comments',
			'constraints' => array(),
			'strategy' => null,
			'init' => true
		);
		$this->assertEqual($expected, MockPost::relations('MockComment')->data());

		MockPost::config(array('meta' => array('connection' => false)));
		MockComment::config(array('meta' => array('connection' => false)));
		MockTag::config(array('meta' => array('connection' => false)));
	}

	public function testSimpleRecordCreation() {
		$comment = MockComment::create(array(
			'author_id' => 451,
			'text' => 'Do you ever read any of the books you burn?'
		));

		$this->assertFalse($comment->exists());
		$this->assertNull($comment->comment_id);

		$expected = 'Do you ever read any of the books you burn?';
		$this->assertEqual($expected, $comment->text);

		$comment = MockComment::create(
			array('author_id' => 111, 'text' => 'This comment should already exist'),
			array('exists' => true)
		);
		$this->assertTrue($comment->exists());
	}

	public function testSimpleFind() {
		$result = MockPost::find('all');
		$this->assertInstanceOf('lithium\data\model\Query', $result['query']);
	}

	public function testMagicFinders() {
		$result = MockPost::findById(5);
		$result2 = MockPost::findFirstById(5);
		$this->assertEqual($result2, $result);

		$expected = array('id' => 5);
		$this->assertEqual($expected, $result['query']->conditions());
		$this->assertEqual('read', $result['query']->type());

		$result = MockPost::findAllByFoo(13, array('order' => array('created_at' => 'desc')));
		$this->assertEmpty($result['query']->data());
		$this->assertEqual(array('foo' => 13), $result['query']->conditions());
		$this->assertEqual(array('created_at' => 'desc'), $result['query']->order());

		$this->assertException('/Method `findFoo` not defined or handled in class/', function() {
			MockPost::findFoo();
		});
	}

	/**
	 * Tests the find 'first' filter on a simple record set.
	 */
	public function testSimpleFindFirst() {
		$result = MockComment::first();
		$this->assertInstanceOf('lithium\data\entity\Record', $result);

		$expected = 'First comment';
		$this->assertEqual($expected, $result->text);
	}

	public function testSimpleFindList() {
		$result = MockComment::find('list');
		$this->assertNotEmpty($result);
		$this->assertInternalType('array', $result);
	}

	public function testFilteredFind() {
		MockComment::applyFilter('find', function($self, $params, $chain) {
			$result = $chain->next($self, $params, $chain);

			if ($result !== null) {
				$result->filtered = true;
			}
			return $result;
		});
		$result = MockComment::first();
		$this->assertTrue($result->filtered);
	}

	public function testCustomFinder() {
		$finder = function() {};
		MockPost::finder('custom', $finder);
		$this->assertIdentical($finder, MockPost::finder('custom'));

		$finder = array(
			'fields' => array('id', 'email'),
			'conditions' => array('id' => 2)
		);
		MockPost::finder('arrayTest', $finder);
		$result = MockPost::find('arrayTest');
		$expected = $finder + array(
			'order' => null,
			'limit' => null,
			'page' => null,
			'with' => array(),
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\MockPost'
		);
		$this->assertEqual($expected, $result['options']);
	}

	public function testCustomFindMethods() {
		$result = MockPost::findFirstById(5);
		$query = $result['query'];
		$this->assertEqual(array('id' => 5), $query->conditions());
		$this->assertEqual(1, $query->limit());
	}

	public function testKeyGeneration() {
		$this->assertEqual('comment_id', MockComment::key());
		$this->assertEqual(array('post_id', 'tag_id'), MockTagging::key());

		$result = MockComment::key(array('comment_id' => 5, 'body' => 'This is a comment'));
		$this->assertEqual(array('comment_id' => 5), $result);

		$result = MockTagging::key(array(
			'post_id' => 2,
			'tag_id' => 5,
			'created' => '2009-06-16 10:00:00'
		));
		$this->assertEqual('id', MockPost::key());
		$this->assertEqual(array('id' => 5), MockPost::key(5));
		$this->assertEqual(array('post_id' => 2, 'tag_id' => 5), $result);

		$key = new stdClass();
		$key->foo = 'bar';

		$this->assertEqual(array('id' => $key), MockPost::key($key));

		$this->assertNull(MockPost::key(array()));

		$model = 'lithium\tests\mocks\data\MockModelCompositePk';
		$this->assertNull($model::key(array('client_id' => 3)));

		$result = $model::key(array('invoice_id' => 5, 'payment' => '100'));
		$this->assertNull($result);

		$expected = array('client_id' => 3, 'invoice_id' => 5);
		$result = $model::key(array(
			'client_id' => 3,
			'invoice_id' => 5,
			'payment' => '100')
		);
		$this->assertEqual($expected, $result);
	}

	public function testValidatesFalse() {
		$post = MockPostForValidates::create();

		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array('email is empty', 'email is not valid')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesWithWhitelist() {
		$post = MockPostForValidates::create();

		$whitelist = array('title');
		$post->title = 'title';
		$result = $post->validates(compact('whitelist'));
		$this->assertTrue($result);

		$post->title = '';
		$result = $post->validates(compact('whitelist'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array('title' => array('please enter a title'));
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesTitle() {
		$post = MockPostForValidates::create(array('title' => 'new post'));

		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'email' => array('email is empty', 'email is not valid')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsNotEmpty() {
		$post = MockPostForValidates::create(array('title' => 'new post', 'email' => 'something'));

		$result = $post->validates();
		$this->assertFalse($result);

		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array('email' => array('email is not valid'));
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsValid() {
		$post = MockPostForValidates::create(array(
			'title' => 'new post', 'email' => 'something@test.com'
		));

		$result = $post->validates();
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testCustomValidationCriteria() {
		$validates = array(
			'title' => 'A custom message here for empty titles.',
			'email' => array(
				array('notEmpty', 'message' => 'email is empty.')
			)
		);
		$post = MockPostForValidates::create(array(
			'title' => 'custom validation', 'email' => 'asdf'
		));

		$result = $post->validates(array('rules' => $validates));
		$this->assertTrue($result);
		$this->assertIdentical(array(), $post->errors());
	}

	public function testValidatesCustomEventFalse() {
		$post = MockPostForValidates::create();
		$events = 'customEvent';

		$this->assertFalse($post->validates(compact('events')));
		$this->assertNotEmpty($post->errors());

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array(
				'email is empty',
				'email is not valid',
				'email is not in 1st list'
			)
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventValid() {
		$post = MockPostForValidates::create(array(
			'title' => 'new post', 'email' => 'something@test.com'
		));

		$events = 'customEvent';
		$result = $post->validates(compact('events'));
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testValidatesCustomEventsFalse() {
		$post = MockPostForValidates::create();

		$events = array('customEvent','anotherCustomEvent');

		$result = $post->validates(compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array(
				'email is empty',
				'email is not valid',
				'email is not in 1st list',
				'email is not in 2nd list'
			)
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventsFirstValid() {
		$post = MockPostForValidates::create(array(
			'title' => 'new post', 'email' => 'foo@bar.com'
		));

		$events = array('customEvent','anotherCustomEvent');

		$result = $post->validates(compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'email' => array('email is not in 2nd list')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventsValid() {
		$post = MockPostForValidates::create(array(
			'title' => 'new post', 'email' => 'something@test.com'
		));

		$events = array('customEvent','anotherCustomEvent');

		$result = $post->validates(compact('events'));
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testValidationInheritance() {
		$product = MockProduct::create();
		$antique = MockSubProduct::create();

		$errors = array(
			'name' => array('Name cannot be empty.'),
			'price' => array(
				'Price cannot be empty.',
				'Price must have a numeric value.'
			)
		);

		$this->assertFalse($product->validates());
		$this->assertEqual($errors, $product->errors());

		$errors += array(
			'refurb' => array('Must have a boolean value.')
		);

		$this->assertFalse($antique->validates());
		$this->assertEqual($errors, $antique->errors());
	}

	public function testErrorsIsClearedOnEachValidates() {
		$post = MockPostForValidates::create(array('title' => 'new post'));
		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$post->email = 'contact@li3.me';
		$result = $post->validates();
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testDefaultValuesFromSchema() {
		$creator = MockCreator::create();

		$expected = array(
			'name' => 'Moe',
			'sign' => 'bar',
			'age' => 0
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(array('name' => 'Homer'));
		$expected = array(
			'name' => 'Homer',
			'sign' => 'bar',
			'age' => 0
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(array(
			'sign' => 'Beer', 'skin' => 'yellow', 'age' => 12, 'hair' => false
		));
		$expected = array(
			'name' => 'Moe',
			'sign' => 'Beer',
			'skin' => 'yellow',
			'age' => 12,
			'hair' => false
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$expected = 'mock_creators';
		$result = MockCreator::meta('source');
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(array('name' => 'Homer'), array('defaults' => false));
		$expected = array(
			'name' => 'Homer'
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);
	}

	public function testCreateCollection() {
		MockCreator::config(array(
			'meta' => array('key' => 'name', 'connection' => 'mockconn')
		));

		$expected = array(
			array('name' => 'Homer'),
			array('name' => 'Bart'),
			array('name' => 'Marge'),
			array('name' => 'Lisa')
		);

		$data = array();
		foreach ($expected as $value) {
			$data[] = MockCreator::create($value, array('defaults' => false));
		}

		$result = MockCreator::create($data, array('class' => 'set'));
		$this->assertCount(4, $result);
		$this->assertInstanceOf('lithium\data\collection\RecordSet', $result);

		$this->assertEqual($expected, $result->to('array', array('indexed' => false)));
	}

	public function testModelWithNoBackend() {
		MockPost::reset();
		$this->assertFalse(MockPost::meta('connection'));
		$schema = MockPost::schema();

		MockPost::config(array('schema' => $this->_altSchema));
		$this->assertEqual($this->_altSchema->fields(), MockPost::schema()->fields());

		$post = MockPost::create(array('title' => 'New post'));
		$this->assertInstanceOf('lithium\data\Entity', $post);
		$this->assertEqual('New post', $post->title);
	}

	public function testSave() {
		MockPost::config(array('schema' => $this->_altSchema));
		MockPost::config(array('schema' => new Schema()));
		$data = array('title' => 'New post', 'author_id' => 13, 'foo' => 'bar');
		$record = MockPost::create($data);
		$result = $record->save();

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());

		MockPost::config(array('schema' => $this->_altSchema));
		$record->tags = array("baz", "qux");
		$otherData = array('body' => 'foobar');
		$result = $record->save($otherData);
		$data['body'] = 'foobar';
		$data['tags'] = array("baz", "qux");

		$expected = array('title' => 'New post', 'author_id' => 13, 'body' => 'foobar');
		$this->assertNotEqual($data, $result['query']->data());
	}

	public function testSaveWithNoCallbacks() {
		MockPost::config(array('schema' => $this->_altSchema));

		$data = array('title' => 'New post', 'author_id' => 13);
		$record = MockPost::create($data);
		$result = $record->save(null, array('callbacks' => false));

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
	}

	public function testSaveWithFailedValidation() {
		$data = array('title' => '', 'author_id' => 13);
		$record = MockPost::create($data);
		$result = $record->save(null, array(
			'validate' => array(
				'title' => 'A title must be present'
			)
		));
		$this->assertFalse($result);
	}

	public function testSaveFailedWithValidationByModelDefinition() {
		$post = MockPostForValidates::create();

		$result = $post->save();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array('email is empty', 'email is not valid')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testSaveFailedWithValidationByModelDefinitionAndTriggeredCustomEvents() {
		$post = MockPostForValidates::create();
		$events = array('customEvent','anotherCustomEvent');

		$result = $post->save(null,compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array(
				'email is empty',
				'email is not valid',
				'email is not in 1st list',
				'email is not in 2nd list'
			)
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testSaveWithValidationAndWhitelist() {
		$post = MockPostForValidates::create();

		$whitelist = array('title');
		$post->title = 'title';
		$result = $post->save(null, compact('whitelist'));
		$this->assertTrue($result);

		$post->title = '';
		$result = $post->save(null, compact('whitelist'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = array('title' => array('please enter a title'));
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testWhitelistWhenLockedUsingCreateForData() {
		MockPost::config(array(
			'schema' => $this->_altSchema,
			'meta' => array(
				'locked' => true,
				'connection' => 'mocksource'
			)
		));

		$data = array('title' => 'New post', 'foo' => 'bar');
		$record = MockPost::create($data);

		$expected = array('title' => 'New post');
		$result = $record->save();
		$this->assertEqual($expected, $result['query']->data());

		$data = array('foo' => 'bar');
		$record = MockPost::create($data);

		$expected = array();
		$result = $record->save();
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testWhitelistWhenLockedUsingSaveForData() {
		MockPost::config(array(
			'schema' => $this->_altSchema,
			'meta' => array(
				'locked' => true,
				'connection' => 'mocksource'
			)
		));

		$data = array('title' => 'New post', 'foo' => 'bar');
		$record = MockPost::create();

		$expected = array('title' => 'New post');
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());

		$data = array('foo' => 'bar');
		$record = MockPost::create();

		$expected = array();
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testWhitelistWhenLockedUsingCreateWithValidAndSaveForInvalidData() {
		MockPost::config(array(
			'schema' => $this->_altSchema,
			'meta' => array(
				'locked' => true,
				'connection' => 'mocksource'
			)
		));

		$data = array('title' => 'New post');
		$record = MockPost::create($data);

		$expected = array('title' => 'New post');
		$data = array('foo' => 'bar');
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testImplicitKeyFind() {
		$result = MockPost::find(10);
		$this->assertEqual('read', $result['query']->type());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
		$this->assertEqual(array('id' => 10), $result['query']->conditions());
	}

	public function testDelete() {
		$record = MockPost::create(array('id' => 5), array('exists' => true));
		$result = $record->delete();
		$this->assertEqual('delete', $result['query']->type());
		$this->assertEqual('mock_posts', $result['query']->source());
		$this->assertEqual(array('id' => 5), $result['query']->conditions());
	}

	public function testMultiRecordUpdate() {
		$result = MockPost::update(
			array('published' => false),
			array('expires' => array('>=' => '2010-05-13'))
		);
		$query = $result['query'];
		$this->assertEqual('update', $query->type());
		$this->assertEqual(array('published' => false), $query->data());
		$this->assertEqual(array('expires' => array('>=' => '2010-05-13')), $query->conditions());
	}

	public function testMultiRecordDelete() {
		$result = MockPost::remove(array('published' => false));
		$query = $result['query'];
		$this->assertEqual('delete', $query->type());
		$this->assertEqual(array('published' => false), $query->conditions());

		$keys = array_keys(array_filter($query->export(MockPost::connection())));
		$this->assertEqual(array('conditions', 'model', 'type', 'source', 'alias'), $keys);
	}

	public function testFindFirst() {
		MockTag::config(array('meta' => array('key' => 'id')));
		$tag = MockTag::find('first', array('conditions' => array('id' => 2)));
		$tag2 = MockTag::find(2);
		$tag3 = MockTag::first(2);

		$expected = $tag['query']->export(MockTag::connection());
		$this->assertEqual($expected, $tag2['query']->export(MockTag::connection()));
		$this->assertEqual($expected, $tag3['query']->export(MockTag::connection()));

		$tag = MockTag::find('first', array(
			'conditions' => array('id' => 2),
			'return' => 'array'
		));

		$expected['return'] = 'array';
		$this->assertTrue($tag instanceof Query);
		$this->assertEqual($expected, $tag->export(MockTag::connection()));
	}

	/**
	 * Tests that varying `count` syntaxes all produce the same query operation (i.e.
	 * `Model::count(...)`, `Model::find('count', ...)` etc).
	 */
	public function testCountSyntax() {
		$base = MockPost::count(array('email' => 'foo@example.com'));
		$query = $base['query'];

		$this->assertEqual('read', $query->type());
		$this->assertEqual('count', $query->calculate());
		$this->assertEqual(array('email' => 'foo@example.com'), $query->conditions());

		$result = MockPost::find('count', array('conditions' => array(
			'email' => 'foo@example.com'
		)));
		$this->assertEqual($query, $result['query']);

		$result = MockPost::count(array('conditions' => array('email' => 'foo@example.com')));
		$this->assertEqual($query, $result['query']);
	}

	public function testSettingNestedObjectDefaults() {
		$schema = MockPost::schema()->append(array(
			'nested.value' => array('type' => 'string', 'default' => 'foo')
		));
		$this->assertEqual('foo', MockPost::create()->nested['value']);

		$data = array('nested' => array('value' => 'bar'));
		$this->assertEqual('bar', MockPost::create($data)->nested['value']);
	}

	/**
	 * Tests that objects can be passed as keys to `Model::find()` and be properly translated to
	 * query conditions.
	 */
	public function testFindByObjectKey() {
		$key = (object) array('foo' => 'bar');
		$result = MockPost::find($key);
		$this->assertEqual(array('id' => $key), $result['query']->conditions());
	}

	public function testLiveConfiguration() {
		MockBadConnection::config(array('meta' => array('connection' => false)));
		$result = MockBadConnection::meta('connection');
		$this->assertFalse($result);
	}

	public function testLazyLoad() {
		$object = MockPost::invokeMethod('_object');
		$object->belongsTo = array('Unexisting');
		MockPost::config();
		MockPost::invokeMethod('_initialize', array('lithium\tests\mocks\data\MockPost'));
		$exception = 'Related model class \'lithium\tests\mocks\data\Unexisting\' not found.';
		$this->assertException($exception, function() {
			MockPost::relations('Unexisting');
		});
	}

	public function testLazyMetadataInit() {
		MockPost::config(array(
			'schema' => new Schema(array(
				'fields' => array(
					'id' => array('type' => 'integer'),
					'name' => array('type' => 'string'),
					'label' => array('type' => 'string')
				)
			))
		));

		$this->assertIdentical('mock_posts', MockPost::meta('source'));
		$this->assertIdentical('name', MockPost::meta('title'));
		$this->assertEmpty(MockPost::meta('unexisting'));

		$config = array(
			'schema' => new Schema(array(
				'fields' => array(
					'id' => array('type' => 'integer'),
					'name' => array('type' => 'string'),
					'label' => array('type' => 'string')
				)
			)),
			'initializers' => array(
				'source' => function($self) {
					return Inflector::tableize($self::meta('name'));
				},
				'name' => function($self) {
					return Inflector::singularize('CoolPosts');
				},
				'title' => function($self) {
					static $i = 1;
					return 'label' . $i++;
				}
			)
		);
		MockPost::reset();
		MockPost::config($config);
		$this->assertIdentical('cool_posts', MockPost::meta('source'));
		$this->assertIdentical('label1', MockPost::meta('title'));
		$this->assertNotIdentical('label2', MockPost::meta('title'));
		$this->assertIdentical('label1', MockPost::meta('title'));
		$meta = MockPost::meta();
		$this->assertIdentical('label1', $meta['title']);
		$this->assertIdentical('CoolPost', MockPost::meta('name'));

		MockPost::reset();
		unset($config['initializers']['title']);
		$config['initializers']['source'] = function($self) {
			return Inflector::underscore($self::meta('name'));
		};
		MockPost::config($config);
		$this->assertIdentical('cool_post', MockPost::meta('source'));
		$this->assertIdentical('name', MockPost::meta('title'));
		$this->assertIdentical('CoolPost', MockPost::meta('name'));

		MockPost::reset();
		MockPost::config($config);
		$expected = array (
			'class' => 'lithium\\tests\\mocks\\data\\MockPost',
			'connection' => false,
			'key' => 'id',
			'name' => 'CoolPost',
			'title' => 'name',
			'source' => 'cool_post'
		);
		$this->assertEqual($expected, MockPost::meta());
	}

	public function testRespondsTo() {
		$this->assertTrue(MockPost::respondsTo('findByFoo'));
		$this->assertTrue(MockPost::respondsTo('findFooByBar'));
		$this->assertFalse(MockPost::respondsTo('fooBarBaz'));
	}

	public function testRespondsToParentCall() {
		$this->assertTrue(MockPost::respondsTo('applyFilter'));
		$this->assertFalse(MockPost::respondsTo('fooBarBaz'));
	}

	public function testRespondsToInstanceMethod() {
		$this->assertFalse(MockPost::respondsTo('foo_Bar_Baz'));
		MockPost::instanceMethods(array(
			'foo_Bar_Baz' => function($entity) {}
		));
		$this->assertTrue(MockPost::respondsTo('foo_Bar_Baz'));
	}

	public function testFieldName() {
		MockPost::bind('hasMany', 'MockTag');
		$relation = MockPost::relations('MockComment');
		$this->assertEqual('mock_comments', $relation->fieldName());

		$relation = MockPost::relations('MockTag');
		$this->assertEqual('mock_tags', $relation->fieldName());

		$relation = MockComment::relations('MockPost');
		$this->assertEqual('mock_post', $relation->fieldName());
	}

	public function testRelationFromFieldName() {
		MockPost::bind('hasMany', 'MockTag');
		$this->assertEqual('MockComment', MockPost::relations('mock_comments')->name());
		$this->assertEqual('MockTag', MockPost::relations('mock_tags')->name());
		$this->assertEqual('MockPost', MockComment::relations('mock_post')->name());
		$this->assertNull(MockPost::relations('undefined'));
	}

	public function testValidateWithRequiredFalse(){
		$post = MockPost::create(array(
			'title' => 'post title',
		));
		$post->validates(array('rules' => array(
			'title' => 'A custom message here for empty titles.',
			'email' => array(
				array('notEmpty', 'message' => 'email is empty.', 'required' => false)
			)
		)));
		$this->assertEmpty($post->errors());
	}

	public function testValidateWithRequiredTrue(){
		$post = MockPost::create(array(
			'title' => 'post title',
		));
		$post->sync(1);
		$post->validates(array('rules' => array(
			'title' => 'A custom message here for empty titles.',
			'email' => array(
				array('notEmpty', 'message' => 'email is empty.', 'required' => true)
			)
		)));
		$this->assertNotEmpty($post->errors());
	}

	public function testValidateWithRequiredNull(){
		$validates = array(
			'title' => 'A custom message here for empty titles.',
			'email' => array(
				array('notEmpty', 'message' => 'email is empty.', 'required' => null)
			)
		);

		$post = MockPost::create(array(
			'title' => 'post title',
		));

		$post->validates(array('rules' => $validates));
		$this->assertNotEmpty($post->errors());

		$post->sync(1);
		$post->validates(array('rules' => $validates));
		$this->assertEmpty($post->errors());
	}
}

?>