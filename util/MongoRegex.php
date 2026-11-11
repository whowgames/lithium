<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Regex;

/**
 * Replacement for the legacy `MongoRegex`, stored as `MongoDB\BSON\Regex`.
 *
 * Like the legacy class, `$regex` (the bare pattern) and `$flags` are real public properties,
 * so they show up in `get_object_vars()`, `(array)` casts, `foreach` and `json_encode()`.
 */
class MongoRegex {
	public $regex;

	public $flags;

	/**
	 * @param string|Regex $regex A delimited pattern with optional flags (`/^foo/i`), or a
	 *        `MongoDB\BSON\Regex`.
	 */
	public function __construct(string|Regex $regex) {
		if ($regex instanceof Regex) {
			$this->regex = $regex->getPattern();
			$this->flags = $regex->getFlags();
			return;
		}
		if (!\preg_match('/^\/(.*)\/([^\/]*)$/s', $regex, $matches)) {
			throw new \InvalidArgumentException("Invalid regex `{$regex}`: expected `/pattern/flags`.");
		}
		$this->regex = $matches[1];
		$this->flags = $matches[2];
	}

	/**
	 * Returns the value as `MongoDB\BSON\Regex`, for passing to the driver.
	 */
	public function toBson(): Regex {
		return new Regex($this->regex, $this->flags);
	}

	public function getFlags(): string {
		return $this->flags;
	}

	public function getPattern(): string {
		return $this->regex;
	}

	public function __toString(): string {
		return "/{$this->regex}/{$this->flags}";
	}
}

?>