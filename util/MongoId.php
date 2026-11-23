<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\ObjectId;

// `Serializable` without `__serialize()` keeps the legacy `C:7:"MongoId":24:{<hex>}` format, so
// cached values stay readable by and from hosts on the alcaeus adapter. PHP 8.1+ deprecates that
// on every load; the declaration is conditional so it runs (and is muted) here, not at compile time.
// shortcut: drop `Serializable` for `__serialize()` once no PHP 8.0 host shares the caches and
// cached legacy values have expired.
$__mongoIdErrorLevel = \error_reporting(\error_reporting() & ~E_DEPRECATED);

if (!\class_exists('MongoId', false)) {
	/**
	 * Replacement for the legacy `MongoId`, stored as `MongoDB\BSON\ObjectId`.
	 *
	 * Like the legacy class, the id is kept in a private property, so `get_object_vars()` and
	 * `(array)` casts give an empty array, and the hex string is read through the magic
	 * `$id` property (`$mongoId->{'$id'}`); `id` is accepted as an alias.
	 */
	class MongoId implements \JsonSerializable, \Serializable {
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

		public function serialize(): string {
			return (string) $this->objectId;
		}

		public function unserialize(string $data): void {
			$this->objectId = new ObjectId($data);
		}

		public function __toString(): string {
			return (string) $this->objectId;
		}
	}
}

\error_reporting($__mongoIdErrorLevel);
unset($__mongoIdErrorLevel);

?>
