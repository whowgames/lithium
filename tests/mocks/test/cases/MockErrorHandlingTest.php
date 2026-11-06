<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\test\cases;

class MockErrorHandlingTest extends \lithium\test\Unit {

	/**
	 * Skip the test.
	 *
	 * @return void
	 */
	public function skip() {
		$this->skipIf(true, 'MockErrorHandlingTest is manually disabled.');
	}

	public function testNotEnoughParams() {
		$this->_arrayPermute();
	}
}

?>