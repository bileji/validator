<?php

namespace Bileji\Validator\Validators;

use ReflectionMethod;
use Bileji\Validator\ValidatorException;

abstract class Validator
{
    const PARAM_NULL = null;

    const LIST_ARRAY_MARK = '_';

    const VALIDATOR_DELIMITER = '|';

    const HIERARCHY_DELIMITER = '.';

    const PARAMETERS_DELIMITER = ',';

    const VALIDATOR_OF_PARAMETERS_DELIMITER = ':';

    const VALIDATOR_ARGS = 'args';

    const VALIDATOR_SYNTAX = 'syntax';

    const VALIDATOR_CONTAINER = 'validator';

    const VALIDATOR_CODE_LABEL = 'code';

    const VALIDATOR_MESSAGE_LABEL = 'message';

    protected $field;

    protected $rules = [];

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
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非整型'
        ],
        'numeric' => [
            self::VALIDATOR_CODE_LABEL => -50004,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field的值:value为非字符串'
        ],
        'required' => [
            self::VALIDATOR_CODE_LABEL => -50005,
            self::VALIDATOR_MESSAGE_LABEL => '字段:field为必填字段'
        ],
    ];

    protected function reverse(array $keys, $value, $eval = '$map', $map = [])
    {
        array_map(function ($key) use (&$eval) {
            $eval .= '[\'' . (is_numeric($key) ? intval($key) : $key) . '\']';
        }, $keys);
        eval($eval . '=' . (empty($value) ? '[]' : '\'' . $value . '\'') . ';');
        return $map;
    }

    // 验证器名称
    protected function setValidatorName($validator)
    {
        return $this->validatorName = self::VALIDATOR_CONTAINER . ucfirst(strval($validator));
    }

    protected function getValidatorName()
    {
        return strpos($this->validatorName, self::VALIDATOR_CONTAINER) === 0 ? str_replace(self::VALIDATOR_CONTAINER, '', strtolower($this->validatorName)) : '';
    }

    protected function syntaxPush(&$syntax, $field)
    {
        return $syntax .= self::HIERARCHY_DELIMITER . $field;
    }

    protected function syntaxPop(&$syntax)
    {
        $pieces = explode(self::HIERARCHY_DELIMITER, $syntax);
        array_pop($pieces);
        return $syntax = implode(self::HIERARCHY_DELIMITER, $pieces);
    }

    // 调取验证器
    protected function callValidator($validator, $args, $syntax)
    {
        if (method_exists($this, $this->setValidatorName($validator))) {
            $value = call_user_func_array([$this, $this->setValidatorName($validator)], $args);
            # 结果为空的数据视为不通过验证
            if (!empty($value)) {
                $this->reverse(explode(self::HIERARCHY_DELIMITER, $syntax), $value, '$this->cacheData', $this->cacheData);
            } else {
                $this->assembleCustomMessage($args);
            }
        } else {
            throw new ValidatorException('验证器' . $validator . '缺失');
        }
    }

    // 语法解析
    protected function parse(array $expressions)
    {
        array_walk($expressions, function ($expression, $field) {
            $validators = is_array($expression) ? $expression : explode(self::VALIDATOR_DELIMITER, $expression);
            array_map(function ($validator) use ($field) {
                $validatorAndParameters = explode(self::VALIDATOR_OF_PARAMETERS_DELIMITER, $validator);
                if (strpos($field, self::HIERARCHY_DELIMITER) !== false) {
                    $rule = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_CONTAINER . self::HIERARCHY_DELIMITER . array_shift($validatorAndParameters)), array_pop($validatorAndParameters));
                    $syntax = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_SYNTAX), $field);
                    $this->rules = array_merge_recursive($this->rules, $rule, $syntax);
                } else {
                    $this->rules[$field][self::VALIDATOR_CONTAINER][array_shift($validatorAndParameters)] = explode(self::PARAMETERS_DELIMITER, array_pop($validatorAndParameters));
                }
            }, $validators);
        });
    }

    // 组装用户自定义消息
    protected function assembleCustomMessage($args)
    {
        $message = str_replace(':field', $this->field, $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_MESSAGE_LABEL]);
        $reflection = new ReflectionMethod(get_class($this), $this->validatorName);
        $parameters = $reflection->getParameters();
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(':' . $parameter->getName(), is_object($args[$index]) || is_array($args[$index]) ? var_export($args[$index], true) : $args[$index], $message);
        }
        $this->errorMessages[$this->field][] = [
            self::VALIDATOR_MESSAGE_LABEL => $message,
            self::VALIDATOR_CODE_LABEL => $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_CODE_LABEL],
        ];
    }


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
        return !empty($value) ? $value : null;
    }
}