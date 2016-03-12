<?php

namespace Bileji\Validator\Validators;

// require for test
require "../ValidatorInterface.php";
require "../ValidatorException.php";
require "./Validator.php";

use Bileji\Validator\ValidatorException;
use Bileji\Validator\ValidatorInterface;

class PositiveValidator extends Validator implements ValidatorInterface
{

    public function execute(array $pendingData, array $expressions)
    {
        $this->parse($expressions);
        foreach ($pendingData as $field => $value) {
            if (empty($field)) {
                throw new ValidatorException('语法错误');
            }
            if (!isset($this->rules[$field])) {
                continue;
            }
            switch (is_array($value)) {
                case false:
                    $this->normalValidator($field, $value, $field);
                    break;
                default:
                    $mark = array_shift(explode(self::HIERARCHY_DELIMITER, $field));
                    $this->mapValidator($value, $this->rules[$mark], $mark);
                    break;
            }
        }
        return $this;
    }

    // 普通验证器
    protected function normalValidator($field, $value, $syntax)
    {
        $this->field = $field;
        foreach ($this->rules[$this->field][self::VALIDATOR_CONTAINER] as $validator => $args) {
            array_unshift($args, $value);
            $this->callValidator($validator, $args, $syntax);
        }
    }

    // 字典验证器
    protected function mapValidator($value, $rules, $syntax)
    {
        if (is_array($rules) && !isset($rules[self::VALIDATOR_CONTAINER])) {
            if (isset($rules[self::LIST_ARRAY_MARK])) {
                $rules = current($rules);
            }
            if ($value != self::PARAM_NULL) {
                if (preg_match('/^\d*$/', implode('', array_keys($value)))) {
                    foreach($value as $n => $one) {
                        $this->syntaxPush($syntax, $n);
                        array_walk($rules, function ($item, $k) use ($one, $syntax) {
                            $this->syntaxPush($syntax, $k);
                            if (isset($one[$k])) {
                                $this->mapValidator($one[$k], $item, $syntax);
                            } else {
                                $this->mapValidator(self::PARAM_NULL, $item, $syntax);
                            }
                        });
                        $this->syntaxPop($syntax);
                    }
                } else {
                    $this->mapValidator(self::PARAM_NULL, $rules, $syntax);
                }
            } else {
                foreach ($rules as $k => $v) {
                    $this->syntaxPush($syntax, $k);
                    if (isset($value[$k])) {
                        $this->mapValidator($value[$k], $v, $syntax);
                    } else {
                        $this->mapValidator(self::PARAM_NULL, $v, $syntax);
                    }
                    $this->syntaxPop($syntax);
                }
            }
        } else if (isset($rules[self::VALIDATOR_CONTAINER])) {
            foreach ($rules[self::VALIDATOR_CONTAINER] as $validator => $args) {
                $this->field = is_array($rules[self::VALIDATOR_SYNTAX]) ? array_shift($rules[self::VALIDATOR_SYNTAX]) : $rules[self::VALIDATOR_SYNTAX];
                array_unshift($args, $value);
                $this->callValidator($validator, $args, $syntax);
            }
        } else {
            throw new ValidatorException('语法错误');
        }
    }

    public function getCode()
    {

    }

    public function getMessage()
    {
        return $this->errorMessages;
    }

    public function withMessage()
    {

    }

    public function getCacheData()
    {
        return $this->cacheData;
    }
}

$validator = new PositiveValidator();

$validator->execute([
    'name' => 'shu c',
    'password' => '123',
    'attr' => [
        [
            'time' => '2016-11-02',
            'price' => 111
        ], [
            'time' => '2016-11-01',
            'price' => 222
        ], [
            'time' => '2016-11-01',
            'price' => 333,
        ]
    ],
    'student' => [
        'class' => [

        ]
    ],
    'test' => [
        'xxx' => 0
    ]
], [
    'name' => 'string',
    'password' => 'string',
    'attr._.time' => 'string',
    'attr._.price' => 'string',
    'attr._.sweet._.one' => 'required|string',
    'attr._.sweet._.two' => 'string',
    'student.class.grad' => 'string',
    'test' => 'map'
]);

var_dump(json_encode($validator->getMessage()));