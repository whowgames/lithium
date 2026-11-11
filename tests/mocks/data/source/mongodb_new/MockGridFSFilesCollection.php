<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongodb_new;

use MongoDB\Collection;
use MongoDB\Driver\CursorInterface;
use MongoDB\UpdateResult;

/**
 * The files collection of a `MockGridFSBucket`, backed by the bucket's `$files`. Filters only
 * support matching on `_id` (or no filter, which matches all files).
 */
class MockGridFSFilesCollection extends Collection {

	public $calls = array();

	protected $_bucket;

	public function __construct(MockGridFSBucket $bucket) {
		$this->_bucket = $bucket;
	}

	public function find(array|object $filter = array(), array $options = array()): CursorInterface {
		$this->calls[] = array('method' => __FUNCTION__) + \compact('filter', 'options');
		return new MockResult(array('data' => $this->_match((array) $filter)));
	}

	public function findOne(array|object $filter = array(), array $options = array()): array|object|null {
		$this->calls[] = array('method' => __FUNCTION__) + \compact('filter', 'options');
		return $this->_match((array) $filter)[0] ?? null;
	}

	public function updateOne(array|object $filter, array|object $update, array $options = array()): UpdateResult {
		$this->calls[] = array('method' => __FUNCTION__) + \compact('filter', 'update', 'options');
		$update = (array) $update;

		foreach ($this->_match((array) $filter) as $doc) {
			$key = (string) $doc['_id'];
			$this->_bucket->files[$key]['doc'] = (array) ($update['$set'] ?? array())
				+ $this->_bucket->files[$key]['doc'];
			break;
		}
		return new class() extends UpdateResult {
			public function __construct() {
			}
		};
	}

	protected function _match(array $filter) {
		$docs = array();

		foreach ($this->_bucket->files as $key => $file) {
			if (!isset($filter['_id']) || (string) $filter['_id'] === $key) {
				$docs[] = $file['doc'];
			}
		}
		return $docs;
	}
}

?>