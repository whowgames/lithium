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
			$result = $this->_resource->current();

			//TODO: Is there still a way to detect if the result is actually a GridFS file? If so, do we need to change its structure?
			/*
			$isFile = ($result instanceof MongoGridFSFile);
			$result = $isFile ? array('file' => $result) + $result->file : $result;
			*/

			$this->_key = $this->_iterator;
			$this->_current = $result;
			return true;
		}

		return false;
	}
}
