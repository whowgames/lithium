<?php
/**
 * Lithium: the most rad php framework
 */

use MongoDB\BSON\Javascript;

/**
 * Replacement for the legacy `MongoCode`, stored as `MongoDB\BSON\Javascript`.
 *
 * Like the legacy class, the code is kept in a private property, so `get_object_vars()`,
 * `(array)` casts and `json_encode()` give an empty array/object.
 */
class MongoCode {
	private Javascript $js;

	public function __construct(string|Javascript $code, array|object|null $scope = null) {
		$this->js = $code instanceof Javascript ? $code : new Javascript($code, $scope ?? []);
	}

	/**
	 * Returns the value as `MongoDB\BSON\Javascript`, for passing to the driver.
	 */
	public function toBson(): Javascript {
		return $this->js;
	}

	public function getCode(): string {
		return $this->js->getCode();
	}

	public function getScope(): ?object {
		return $this->js->getScope();
	}

	public function __toString(): string {
		return $this->js->getCode();
	}
}

?>