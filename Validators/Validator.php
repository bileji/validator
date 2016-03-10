<?php

namespace Bileji\Validator\Validators;

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

    protected $field;

    protected $rules = [];

    protected $cacheData = [];

    // 调取验证器
    protected function callValidator($validator, $args)
    {
        if (method_exists($this, $this->getValidatorName($validator))) {
            $value = call_user_func_array([$this, $this->getValidatorName($validator)], $args);
            if (!empty($value)) {
                $this->cacheData[$this->field] = $value;
            }
        } else {
            throw new ValidatorException('验证器' . $validator . '缺失');
        }
    }

    // 语法解析
    protected function parse(array $expressions)
    {
        array_walk($expressions, function($expression, $field) {
            $validators = is_array($expression) ? $expression : explode(self::VALIDATOR_DELIMITER, $expression);
            array_map(function($validator) use ($field) {
                $validatorAndParameters = explode(self::VALIDATOR_OF_PARAMETERS_DELIMITER, $validator);
                if (strpos($field, self::HIERARCHY_DELIMITER) !== false) {
                    $rule = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_CONTAINER . self::HIERARCHY_DELIMITER . array_shift($validatorAndParameters)), array_pop($validatorAndParameters));
                    $syntax = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_SYNTAX),  '\'' . $field . '\'');
                    $this->rules = array_merge_recursive($this->rules, $rule, $syntax);
                } else {
                    $this->rules[$field][self::VALIDATOR_CONTAINER][array_shift($validatorAndParameters)] = explode(self::PARAMETERS_DELIMITER, array_pop($validatorAndParameters));
                }
            }, $validators);
        });
    }

    protected function reverse(array $keys, $value)
    {
        $map = []; $mapStr = '$map';
        array_map(function($key) use (&$mapStr) {
            $mapStr .= '[\'' . (is_numeric($key) ? intval($key) : $key) . '\']';
        }, $keys);
        eval($mapStr . '=' . (empty($value) ? '[\'\']' : $value) . ';');
        return $map;
    }

    // 验证器名称
    protected function getValidatorName($validator)
    {
        return 'validator' . ucfirst(strval($validator));
    }


    /******************************************** validator ********************************************/

    protected function validatorInt()
    {

    }

    protected function validatorMap()
    {

    }

    protected function validatorList()
    {

    }

    protected function validatorString()
    {
        var_dump($this->field);
    }

    protected function validatorRequired()
    {

    }
}