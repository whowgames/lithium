<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Regex;

/**
 * Wrapper for new MongoDB\BSON\Regex to make it behave like the legacy MongoRegex.
 */
class MongoRegex {
	public ?Regex $_regex = null;

	public string $regex {
		get {
			return $this->_regex->getPattern();
		}
		set(string $value) {
			$this->_regex = new Regex($value, $this->flags);
		}
	}

	public string $flags {
		get {
			return $this->_regex->getFlags();
		}
		set(string $value) {
			$this->_regex = new Regex($this->regex, $value);
		}
	}

	public function __construct(string|Regex $regex) {
		if ($regex instanceof Regex) {
			$this->_regex = $regex;
		} else {
			// MongoRegex expects a string containing delimiters and possibly flags;
			// MongoDB\BSON\Regex expects the bare pattern and flags as separate arguments.
			preg_match('/^\/(.*)\/([^/]*)$/', $regex, $matches);
			$this->_regex = new Regex($matches[1], $matches[2]);
		}
	}

	public function getFlags(): string {
		return $this->_regex->getFlags();
	}

	public function getPattern(): string {
		return $this->_regex->getPattern();
	}

	public function jsonSerialize(): mixed {
		return $this->_regex->jsonSerialize();
	}

	public function __toString(): string {
		return $this->_regex->__toString();
	}
}

?>
