<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Javascript;

/**
 * Wrapper for new MongoDB\BSON\Javascript to make it behave like the legacy MongoCode.
 */
class MongoCode implements \JsonSerializable {
	public ?Javascript $js = null;

	public function __construct(string|Javascript $code, array|object|null $scope = null) {
		if ($code instanceof Javascript) {
			$this->js = $code;
		} else {
			$this->js = new Javascript($code, is_null($scope) ? array() : $scope);
		}
	}

	public function getCode(): string {
		return $this->js->getCode();
	}

	public function getScope(): ?object {
		return $this->js->getScope();
	}

	public function jsonSerialize(): mixed {
		// MongoCode encodes as an empty object in the legacy driver. 🤷
		return new \stdClass();
	}

	public function __toString(): string {
		return $this->js->__toString();
	}
}

?>
