<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\ObjectId;

/**
 * Replacement for the legacy `MongoId`, stored as `MongoDB\BSON\ObjectId`.
 *
 * Like the legacy class, the id is kept in a private property, so `get_object_vars()` and
 * `(array)` casts give an empty array, and the hex string is read through the magic
 * `$id` property (`$mongoId->{'$id'}`); `id` is accepted as an alias.
 */
class MongoId implements \JsonSerializable {
	private ObjectId $objectId;

	public function __construct(string|MongoId|ObjectId|null $id = null) {
		if ($id instanceof ObjectId) {
			$this->objectId = $id;
		} elseif ($id instanceof MongoId) {
			$this->objectId = $id->toBson();
		} else {
			$this->objectId = new ObjectId($id);
		}
	}

	public function __get($name) {
		return match ($name) {
			'$id', 'id' => (string) $this->objectId,
			default => null,
		};
	}

	public function __set($name, $value) {
		match ($name) {
			'$id', 'id' => $this->objectId = new ObjectId($value),
		};
	}

	public function __isset($name) {
		return \in_array($name, ['$id', 'id'], true);
	}

	/**
	 * Returns the value as `MongoDB\BSON\ObjectId`, for passing to the driver.
	 */
	public function toBson(): ObjectId {
		return $this->objectId;
	}

	public static function getHostname(): string {
		return \gethostname();
	}

	public function getTimestamp(): int {
		return $this->objectId->getTimestamp();
	}

	public function jsonSerialize(): mixed {
		return ['$id' => (string) $this->objectId];
	}

	public function __toString(): string {
		return (string) $this->objectId;
	}
}

?>