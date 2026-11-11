<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongodb_new;

use lithium\data\source\MongoDBNew;
use MongoDB\GridFS\Bucket;

/**
 * Replacement for the legacy `MongoGridFSFile`, built on `MongoDB\GridFS\Bucket`.
 *
 * GridFS reads made through `MongoDBNew` put an instance of this class into the `file` field
 * of each record, as the legacy `MongoDb` source did with `MongoGridFSFile`. It offers the
 * same commonly used API: the `$file` property (the files collection document) and
 * `getFilename()`, `getSize()`, `getBytes()`, `getResource()` and `write()`.
 */
class GridFSFile {

	/**
	 * The document from the GridFS files collection, with BSON values converted to the
	 * legacy wrapper types (`MongoId`, `MongoDate`, ...).
	 *
	 * @var array
	 */
	public $file = [];

	/**
	 * The bucket the file belongs to.
	 *
	 * @var \MongoDB\GridFS\Bucket
	 */
	protected $_bucket = null;

	/**
	 * @param \MongoDB\GridFS\Bucket $bucket The bucket the file belongs to.
	 * @param array $file The document from the files collection.
	 */
	public function __construct(Bucket $bucket, array $file) {
		$this->_bucket = $bucket;
		$this->file = $file;
	}

	/**
	 * Returns the bucket the file belongs to.
	 *
	 * @return \MongoDB\GridFS\Bucket
	 */
	public function getBucket() {
		return $this->_bucket;
	}

	/**
	 * Returns the file's name, or `null` if it has none.
	 *
	 * @return string|null
	 */
	public function getFilename() {
		return $this->file['filename'] ?? null;
	}

	/**
	 * Returns the file's size in bytes.
	 *
	 * @return integer
	 */
	public function getSize() {
		return (int) ($this->file['length'] ?? 0);
	}

	/**
	 * Returns the file's contents.
	 *
	 * @return string
	 */
	public function getBytes() {
		return \stream_get_contents($this->getResource());
	}

	/**
	 * Returns a readable stream of the file's contents.
	 *
	 * @return resource
	 */
	public function getResource() {
		return $this->_bucket->openDownloadStream(MongoDBNew::toBson($this->file['_id']));
	}

	/**
	 * Writes the file's contents to the local filesystem.
	 *
	 * @param string|null $filename The target path. Defaults to the file's stored name.
	 * @return integer The number of bytes written.
	 */
	public function write($filename = null) {
		$filename = $filename ?? $this->getFilename();

		try {
			$target = \fopen($filename, 'wb');
		} catch (\ValueError $e) {
			throw new \RuntimeException("Could not open `{$filename}` for writing.");
		}
		if (!$target) {
			throw new \RuntimeException("Could not open `{$filename}` for writing.");
		}

		try {
			$this->_bucket->downloadToStream(MongoDBNew::toBson($this->file['_id']), $target);
		} finally {
			\fclose($target);
		}

		return $this->getSize();
	}
}
