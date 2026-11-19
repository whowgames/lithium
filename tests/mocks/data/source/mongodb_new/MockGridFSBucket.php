<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongodb_new;

use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;

/**
 * An in-memory `MongoDB\GridFS\Bucket`. Stored files are kept in `$files`, keyed by the string
 * form of their id, as `array('doc' => <files collection document>, 'bytes' => <contents>)`.
 * Every bucket operation is recorded in `$calls`.
 */
class MockGridFSBucket extends Bucket {

	public $files = array();

	public $calls = array();

	protected $_bucketName;

	protected $_filesCollection;

	public function __construct($bucketName = 'fs') {
		$this->_bucketName = $bucketName;
		$this->_filesCollection = new MockGridFSFilesCollection($this);
	}

	/**
	 * Stores a file without recording a call, to set up test fixtures.
	 */
	public function seed($id, $bytes, array $doc = array()) {
		$this->files[(string) $id] = array(
			'doc' => array('_id' => $id, 'length' => \strlen($bytes)) + $doc,
			'bytes' => $bytes
		);
	}

	public function getBucketName(): string {
		return $this->_bucketName;
	}

	public function getFilesCollection(): Collection {
		return $this->_filesCollection;
	}

	public function uploadFromStream(string $filename, $source, array $options = array()): mixed {
		$id = $options['_id'] ?? new ObjectId();
		$this->calls[] = array('method' => __FUNCTION__) + \compact('filename', 'options');
		$this->seed($id, \stream_get_contents($source), \compact('filename'));
		return $id;
	}

	public function delete(mixed $id): void {
		$this->calls[] = array('method' => __FUNCTION__) + \compact('id');

		if (!isset($this->files[(string) $id])) {
			throw FileNotFoundException::byId($id, "test.{$this->_bucketName}.files");
		}
		unset($this->files[(string) $id]);
	}

	public function openDownloadStream(mixed $id) {
		$this->calls[] = array('method' => __FUNCTION__) + \compact('id');

		if (!isset($this->files[(string) $id])) {
			throw FileNotFoundException::byId($id, "test.{$this->_bucketName}.files");
		}
		$stream = \fopen('php://temp', 'w+b');
		\fwrite($stream, $this->files[(string) $id]['bytes']);
		\rewind($stream);
		return $stream;
	}

	public function downloadToStream(mixed $id, $destination): void {
		\stream_copy_to_stream($this->openDownloadStream($id), $destination);
	}
}

?>