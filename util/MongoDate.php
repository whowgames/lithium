<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\UTCDateTime;

/**
 * Wrapper for new MongoDB\BSON\UTCDateTime to make it behave like the legacy MongoDate.
 */
class MongoDate implements \JsonSerializable {
	public ?UTCDateTime $dt = null;

	public function __get($name) {
		return match ($name) {
			'sec' => $this->dt->toDateTime()->getTimestamp(),
			'usec' => (int) $this->dt->toDateTime()->format('u'),
			default => null,
		};
	}

	public function __set($name, $value) {
		match ($name) {
			'sec' => $this->dt = new UTCDateTime($value * 1000 + intdiv($this->usec, 1000)),
			'usec' => $this->dt = new UTCDateTime($this->sec * 1000 + intdiv($value, 1000)),
		};
	}

	public function __isset($name) { return in_array($name, ['sec', 'usec'], true); }

	public function __construct(int|UTCDateTime|null $sec = null, int $usec = 0){
		if (is_null($sec)) {
			$this->dt = new UTCDateTime();
		} elseif ($sec instanceof UTCDateTime) {
			$this->dt = $sec;
		} else {
			// UTCDateTime only takes milliseconds.
			$this->dt = new UTCDateTime($sec * 1000 + intdiv($usec, 1000));
		}
	}

	public function jsonSerialize(): mixed {
		return ['sec' => $this->sec, 'usec' => $this->usec];
	}

	public function toDateTime(): DateTime {
		return $this->dt->toDateTime();
	}

	public function toDateTimeImmutable(): DateTimeImmutable {
		return $this->dt->toDateTimeImmutable();
	}

	public function __toString(): string {
		return $this->dt->__toString();
	}
}

?>
