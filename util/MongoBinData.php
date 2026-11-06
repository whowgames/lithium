<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Binary;

/**
 * Wrapper for new MongoDB\BSON\Binary to make it behave like the legacy MongoBinData.
 */
class MongoBinData implements \JsonSerializable {
	const GENERIC = 0 ;
	const FUNC = 1 ;
	const BYTE_ARRAY = 2 ;
	const UUID = 3 ;
	const UUID_RFC4122 = 4 ;
	const MD5 = 5 ;
	const CUSTOM = 128 ;

	public ?Binary $binary = null;

	public function __get($name) {
		return match ($name) {
			'bin' => $this->binary->getData(),
			'type' => $this->binary->getType(),
			default => null,
		};
	}

	public function __set($name, $value) {
		match ($name) {
			'bin' => $this->binary = new Binary($value, $this->getType()),
			'type' => $this->binary = new Binary($this->getData(), $value),
		};
	}

	public function __isset($name) { return in_array($name, ['bin', 'type'], true); }

	public function __construct(string|Binary $data, int $type = MongoBinData::BYTE_ARRAY) {
		if ($data instanceof Binary) {
			$this->binary = $data;
		} else {
			$this->binary = new Binary($data, $type);
		}
	}

	public function getData(): string {
		return $this->binary->getData();
	}

	public function getType(): int {
		return $this->binary->getType();
	}

	public function getVectorType(): MongoDB\BSON\VectorType {
		return $this->binary->getVectorType();
	}

	public function jsonSerialize(): mixed {
		return ['bin' => $this->bin, 'type' => $this->type];
	}

	public function toArray(): array {
		return $this->binary->toArray();
	}

	public function __toString(): string {
		return $this->binary->__toString();
	}
}

?>
