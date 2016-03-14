<?php

namespace Bileji\Validator;

abstract class ValidatorHeader
{
    const PARAM_NULL = null;

    const LIST_ARRAY_MARK = '_';

    const VALIDATOR_ARGS = 'args';

    const VALIDATOR_DELIMITER = '|';

    const HIERARCHY_DELIMITER = '.';

    const PARAMETERS_DELIMITER = ',';

    const VALIDATOR_SYNTAX = 'syntax';

    const VALIDATOR_CODE_LABEL = 'code';

    const VALIDATOR_CONTAINER = 'validator';

    const VALIDATOR_MESSAGE_LABEL = 'message';

    const VALIDATOR_OF_PARAMETERS_DELIMITER = ':';

    const VALIDATOR_BREAK_MD5 = '7682a8b943f68c0372da27eeeb413537';

    protected $error;

    protected $field;

    protected $errors;

    protected $rules = [];

    protected $fail = false;

    protected $cacheData = [];

    protected $validatorName = '';

    protected $errorMessages = [];

    protected $defaultMessagesTemplate = [
        'map' => [
            self::VALIDATOR_CODE_LABEL => -50001,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非字典'
        ],
        'list' => [
            self::VALIDATOR_CODE_LABEL => -50002,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非列表'
        ],
        'string' => [
            self::VALIDATOR_CODE_LABEL => -50003,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非字符串'
        ],
        'numeric' => [
            self::VALIDATOR_CODE_LABEL => -50004,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非整型'
        ],
        'required' => [
            self::VALIDATOR_CODE_LABEL => -50005,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field为必填字段'
        ],
    ];

    protected function validatorMap($value)
    {
        return is_array($value) && !preg_match('/^\d*$/', implode('', array_keys($value))) ? $value : null;
    }

    protected function validatorList($value)
    {
        return is_array($value) && preg_match('/^\d*$/', implode('', array_keys($value))) ? $value : null;
    }

    protected function validatorString($value)
    {
        return is_string($value) ? $value : null;
    }

    protected function validatorNumeric($value)
    {
        return is_numeric($value) ? $value : null;
    }

    protected function validatorRequired($value)
    {
        return !empty($value) ? $value : self::VALIDATOR_BREAK_MD5;
    }
}