<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Binary;

/**
 * Replacement for the legacy `MongoBinData`, stored as `MongoDB\BSON\Binary`.
 *
 * Like the legacy class, `$bin` (the raw bytes) and `$type` are real public properties, so
 * they show up in `get_object_vars()`, `(array)` casts and `foreach`. As in the legacy
 * class, `json_encode()` fails when the bytes aren't valid UTF-8.
 */
class MongoBinData {
	const GENERIC = 0;
	const FUNC = 1;
	const BYTE_ARRAY = 2;
	const UUID = 3;
	const UUID_RFC4122 = 4;
	const MD5 = 5;
	const CUSTOM = 128;

	public $bin;

	public $type;

	public function __construct(string|Binary $data, int $type = MongoBinData::BYTE_ARRAY) {
		if ($data instanceof Binary) {
			$type = $data->getType();
			$data = $data->getData();
		}
		$this->bin = $data;
		$this->type = $type;
	}

	/**
	 * Returns the value as `MongoDB\BSON\Binary`, for passing to the driver.
	 */
	public function toBson(): Binary {
		return new Binary($this->bin, $this->type);
	}

	public function getData(): string {
		return $this->bin;
	}

	public function getType(): int {
		return $this->type;
	}

	public function getVectorType(): MongoDB\BSON\VectorType {
		return $this->toBson()->getVectorType();
	}

	public function toArray(): array {
		return $this->toBson()->toArray();
	}

	public function __toString(): string {
		return '<Mongo Binary Data>';
	}
}

?>