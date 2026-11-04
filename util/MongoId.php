<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\ObjectId;

/**
 * Wrapper for new MongoDB\BSON\ObjectId to make it behave like the legacy MongoId.
 */
class MongoId {
	public ?ObjectId $oid = null;

	public ?string $id {
		get {
			return $this->oid->__toString();
		}
		set(?string $value) {
			$this->oid = new ObjectId($value);
		}
	}

	public function __construct(string|MongoId|ObjectId $id = null) {
		if ($id instanceof ObjectId) {
			$this->oid = $id;
		} else {
			$this->oid = new ObjectId($id);
		}
	}

	public static function getHostname(): string {
		return gethostname();
	}

	public function getTimestamp(): int {
		return $this->oid->getTimestamp();
	}

	public function jsonSerialize(): mixed {
		return $this->oid->jsonSerialize();
	}

	public function __toString(): string {
		return $this->oid->__toString();
	}

	/*
	 * Unused by our code:
	 *
	public function getInc(): int {
	}

	public function getPID(): int {
	}

	public static function isValid(mixed $value): bool {
	}

	public static function __set_state(array $props): MongoId {
	}
	 */
}

?>
