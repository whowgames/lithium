<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\UTCDateTime;

/**
 * Replacement for the legacy `MongoDate`, stored as `MongoDB\BSON\UTCDateTime`.
 *
 * Like the legacy class, `$sec` and `$usec` are real public properties, so they show up in
 * `get_object_vars()`, `(array)` casts, `foreach` and `json_encode()`. `$usec` is truncated
 * to milliseconds, the precision BSON dates have.
 */
class MongoDate {
	public $sec;

	public $usec;

	public function __construct(int|UTCDateTime|null $sec = null, int $usec = 0) {
		if ($sec instanceof UTCDateTime) {
			$ms = (int) (string) $sec;
			$sec = \intdiv($ms, 1000);
			$usec = ($ms % 1000) * 1000;

			if ($usec < 0) {
				$sec--;
				$usec += 1000000;
			}
		} elseif ($sec === null) {
			[$usec, $sec] = \explode(' ', \microtime());
			$sec = (int) $sec;
			$usec = (int) ($usec * 1000000);
		}
		$this->sec = $sec;
		$this->usec = $usec - $usec % 1000;
	}

	/**
	 * Returns the value as `MongoDB\BSON\UTCDateTime`, for passing to the driver.
	 */
	public function toBson(): UTCDateTime {
		return new UTCDateTime($this->sec * 1000 + \intdiv($this->usec, 1000));
	}

	public function toDateTime(): DateTime {
		return $this->toBson()->toDateTime();
	}

	public function toDateTimeImmutable(): DateTimeImmutable {
		return $this->toBson()->toDateTimeImmutable();
	}

	/**
	 * Same format as the legacy class: fraction of a second, then seconds
	 * (`0.12300000 1731234567`).
	 */
	public function __toString(): string {
		return \sprintf('%.8f', $this->usec / 1000000) . ' ' . $this->sec;
	}
}

?>