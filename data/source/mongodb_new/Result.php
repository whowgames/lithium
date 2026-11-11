<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongodb_new;

use MongoDB\Driver\Exception\RuntimeException;

class Result extends \lithium\data\source\Result {
	public function prev() {
		return null;
	}

	protected function _fetchFromCache() {
		return null;
	}

	/**
	 * Whether the driver cursor has been positioned on its first document yet.
	 *
	 * @var boolean
	 */
	protected $_cursorStarted = false;

	/**
	 * The GridFS bucket, when the cursor reads from a GridFS files collection. Each record is
	 * then returned with its file attached as `file` (see `GridFSFile`), like the legacy
	 * `MongoDb` source did with `MongoGridFSFile`.
	 *
	 * @var \MongoDB\GridFS\Bucket|null
	 */
	protected $_bucket = null;

	/**
	 * Autoconfig.
	 */
	protected $_autoConfig = array('resource', 'bucket');

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return boolean Return `true` on success or `false` if it is not valid.
	 */
	protected function _fetchFromResource() {
		if (!$this->_resource) {
			return false;
		}

		try {
			if (!$this->_cursorStarted) {
				$this->_cursorStarted = true;
				$this->_resource->rewind();
			} else {
				$this->_resource->next();
			}
		} catch (RuntimeException $e) {
			return false;
		}

		if ($this->_resource->valid()) {
			$result = \lithium\data\source\MongoDBNew::fromBson($this->_resource->current());

			if ($this->_bucket && \is_array($result)) {
				$result = array('file' => new GridFSFile($this->_bucket, $result)) + $result;
			}

			$this->_key = $this->_iterator;
			$this->_current = $result;
			return true;
		}

		return false;
	}
}
