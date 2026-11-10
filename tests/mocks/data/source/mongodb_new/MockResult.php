<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongodb_new;

use MongoDB\BSON\Int64;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Server;

/**
 * An array-backed stand-in for a `MongoDB\Driver\Cursor`, as returned by
 * `MongoDB\Collection::find()`. Pass the documents as `data`.
 */
class MockResult implements CursorInterface {

	protected $_data = array();

	protected $_position = 0;

	public function __construct(array $config = array()) {
		$this->_data = \array_values($config['data'] ?? array());
	}

	public function current(): object|array|null {
		return $this->_data[$this->_position] ?? null;
	}

	public function key(): ?int {
		return $this->valid() ? $this->_position : null;
	}

	public function next(): void {
		$this->_position++;
	}

	public function rewind(): void {
		$this->_position = 0;
	}

	public function valid(): bool {
		return isset($this->_data[$this->_position]);
	}

	public function getId(): Int64 {
		return new Int64(0);
	}

	public function getServer(): Server {
		throw new \LogicException('Not implemented in the mock.');
	}

	public function isDead(): bool {
		return !$this->valid();
	}

	public function setTypeMap(array $typemap): void {
	}

	public function toArray(): array {
		return $this->_data;
	}
}

?>