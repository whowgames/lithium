<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Binary;

/**
 * Wrapper for new MongoDB\BSON\Binary to make it behave like the legacy MongoBinData.
 */
class MongoBinData {
	const int GENERIC = 0 ;
	const int FUNC = 1 ;
	const int BYTE_ARRAY = 2 ;
	const int UUID = 3 ;
	const int UUID_RFC4122 = 4 ;
	const int MD5 = 5 ;
	const int CUSTOM = 128 ;

	public ?Binary $binary = null;

	public string $bin {
		get {
			return $this->getData();
		}
		set(string $value) {
			$this->binary = new Binary($value, $this->getType());
		}
	}

	public int $type {
		get {
			return $this->getType();
		}
		set(int $value) {
			$this->binary = new Binary($this->getData(), $value);
		}
	}

	public function __construct(string|Binary $data, int $type = MongoBinData::BYTE_ARRAY) {
		if ($data instanceof Binary) {
			$this->binary = $data;
		} else {
			$this->binary = new Binary($data, $type);
		}
	}

	public function getData(): string {
		return $this->regex->getData();
	}

	public function getType(): int {
		return $this->regex->getType();
	}

	public function getVectorType(): MongoDB\BSON\VectorType {
		return $this->regex->getVectorType();
	}

	public function jsonSerialize(): mixed {
		return $this->regex->jsonSerialize();
	}

	public function toArray(): array {
		return $this->regex->toArray();
	}

	public function __toString(): string {
		return $this->regex->__toString();
	}
}

?>
