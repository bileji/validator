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

    protected $customMessages = [];

    protected $defaultMessagesTemplate = [
        'map' => [
            self::VALIDATOR_CODE_LABEL => -50001,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不为字典'
        ],
        'list' => [
            self::VALIDATOR_CODE_LABEL => -50002,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不为列表'
        ],
        'string' => [
            self::VALIDATOR_CODE_LABEL => -50003,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不为字符串'
        ],
        'numeric' => [
            self::VALIDATOR_CODE_LABEL => -50004,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不为整型'
        ],
        'float' => [
            self::VALIDATOR_CODE_LABEL => -50005,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不为浮点型'
        ],
        'required' => [
            self::VALIDATOR_CODE_LABEL => -50006,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field为必填字段'
        ],
        'between' => [
            self::VALIDATOR_CODE_LABEL => -50007,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value必须介于:min-:max之间'
        ],
        'url' => [
            self::VALIDATOR_CODE_LABEL => -50008,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不是正确的url格式'
        ],
        'email' => [
            self::VALIDATOR_CODE_LABEL => -50009,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不符合邮箱格式'
        ],
        'phone' => [
            self::VALIDATOR_CODE_LABEL => -50010,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不符合手机号格式'
        ],
        'chinese' => [
            self::VALIDATOR_CODE_LABEL => -50011,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不符合中文格式'
        ],
        'regex' => [
            self::VALIDATOR_CODE_LABEL => -50012,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不符合:regex格式'
        ],
        'before' => [
            self::VALIDATOR_CODE_LABEL => -50013,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不在:time之前'
        ],
        'after' => [
            self::VALIDATOR_CODE_LABEL => -50014,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不在:time之后'
        ],
        'enum' => [
            self::VALIDATOR_CODE_LABEL => -50015,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value不在枚举的值:enum之中'
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

    protected function validatorFloat($value)
    {
        return is_float($value) ? $value : null;
    }

    protected function validatorRequired($value)
    {
        return !empty($value) ? $value : self::VALIDATOR_BREAK_MD5;
    }

    protected function validatorBetween($value, $min, $max)
    {
        return $value >= $min && $value <= $max ? $value : null;
    }

    protected function validatorUrl($value)
    {
        return preg_match('/^https?:\/\/(([a-zA-Z0-9_-])+(\.)?)*(:\d+)?(\/((\.)?(\?)?=?&?[a-zA-Z0-9_-](\?)?)*)*$/i', $value) ? $value : null;
    }

    protected function validatorEmail($value)
    {
        return preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $value) ? $value : null;
    }

    protected function validatorPhone($value)
    {
        return preg_match('/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/', $value) ? $value : null;
    }

    protected function validatorChinese($value)
    {
        return preg_match('/[\u4e00-\u9fa5]/gm', $value) ? $value : null;
    }

    protected function validatorRegex($value, $regex)
    {
        return preg_match($regex, $value) ? $value : null;
    }

    protected function validatorBefore($value, $time)
    {
        return ($value < !is_int($time) ? strtotime($time) : $time) ? $value : null;
    }

    protected function validatorAfter($value, $time)
    {
        return ($value < !is_int($time) ? strtotime($time) : $time) ? $value : null;
    }

    protected function validatorDefault($value, $default)
    {
        return empty($value) ? $default : $value;
    }

    protected function validatorEnum($value, $enum)
    {
        $enum = explode('-', $enum);
        foreach($enum as $item) {
            $enum[] = intval($item);
        }
        return in_array($value, $enum) ? $value : null;
    }
}
