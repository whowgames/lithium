<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Regex;

/**
 * Wrapper for new MongoDB\BSON\Regex to make it behave like the legacy MongoRegex.
 */
class MongoRegex implements \JsonSerializable {
	public ?Regex $_regex = null;

	public function __get($name) {
		return match ($name) {
			'regex' => $this->_regex->getPattern(),
			'flags' => $this->_regex->getFlags(),
			default => null,
		};
	}

	public function __set($name, $value) {
		match ($name) {
			'regex' => $this->_regex = new Regex($value, $this->flags),
			'flags' => $this->_regex = new Regex($this->regex, $value),
		};
	}

	public function __isset($name) { return in_array($name, ['regex', 'flags'], true); }

	public function __construct(string|Regex $regex) {
		if ($regex instanceof Regex) {
			$this->_regex = $regex;
		} else {
			// MongoRegex expects a string containing delimiters and possibly flags;
			// MongoDB\BSON\Regex expects the bare pattern and flags as separate arguments.
			preg_match('/^\/(.*)\/([^\/]*)$/', $regex, $matches);
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
		return ['regex' => $this->regex, 'flags' => $this->flags];
	}

	public function __toString(): string {
		return $this->_regex->__toString();
	}
}

?>
