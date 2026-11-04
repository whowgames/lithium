<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\UTCDateTime;

/**
 * Wrapper for new MongoDB\BSON\UTCDateTime to make it behave like the legacy MongoDate.
 */
class MongoDate {
	public ?UTCDateTime $dt = null;

	public int $sec {
		get {
			return $this->dt->toDateTime()->getTimestamp();
		}
		set(int $value) {
			$this->dt = new UTCDateTime($value * 1000 + intdiv($this->usec, 1000));
		}
	}

	public int $usec {
		get {
			return $this->dt->toDateTime()->getMicrosecond();
		}
		set(int $value) {
			$this->dt = new UTCDateTime($this->sec * 1000 + intdiv($value, 1000));
		}
	}

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
		return $this->dt->jsonSerialize();
	}

	public function toDateTime(): DateTime {
		return $this->dt->toDateTime();
	}

	public function toDateTimeImmutable(): DateTime {
		return $this->dt->toDateTimeImmutable();
	}

	public function __toString(): string {
		return $this->dt->__toString();
	}
}

?>
