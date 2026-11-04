<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source\mongodb_new;

require_once __DIR__ . '/../../../util/MongoId.php';
require_once __DIR__ . '/../../../util/MongoCode.php';
require_once __DIR__ . '/../../../util/MongoDate.php';
require_once __DIR__ . '/../../../util/MongoRegex.php';
require_once __DIR__ . '/../../../util/MongoBinData.php';

use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use MongoBinData;

class Schema extends \lithium\data\DocumentSchema {

	protected $_handlers = [];

	protected $_types = [
		'MongoId'                  => 'id',
		'MongoDate'                => 'date',
		'MongoCode'                => 'code',
		'MongoBinData'             => 'binary',
		'MongoDB\BSON\ObjectId'    => 'id',
		'MongoDB\BSON\UTCDateTime' => 'date',
		'MongoDB\BSON\Javascript'  => 'code',
		'MongoDB\BSON\Binary'      => 'binary',
		'datetime'                 => 'date',
		'timestamp'                => 'date',
		'int'                      => 'integer'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'fields'` _array_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['fields' => ['_id' => ['type' => 'id']]];
		parent::__construct(array_filter($config) + $defaults);
	}

	protected function _init() {
		$this->_autoConfig[] = 'handlers';
		parent::_init();

		$this->_handlers += [
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = intval((is_numeric($v) ? $v : strtotime($v)) * 1000);
				return !$v ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		];
	}
}

?>
