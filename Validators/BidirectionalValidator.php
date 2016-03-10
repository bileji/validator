<?php

namespace Bileji\Validator\Validators;

// require for test
require "../ValidatorInterface.php";
require "../ValidatorException.php";
require "./Validator.php";

use Bileji\Validator\ValidatorException;
use Bileji\Validator\ValidatorInterface;

class BidirectionalValidator extends Validator implements ValidatorInterface
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
                    $this->normalValidator($field, $value);
                    break;
                default:
                    $this->mapValidator($value, $this->rules[array_shift(explode(self::HIERARCHY_DELIMITER, $field))]);
                    break;
            }
        }
        return $this;
    }

    // 普通验证器
    protected function normalValidator($field, $value)
    {
        $this->field = $field;
        foreach ($this->rules[$this->field][self::VALIDATOR_CONTAINER] as $validator => $args) {
            if (!isset($this->cacheData[$this->field])) {
                $this->cacheData[$this->field] = $value;
            }
            array_unshift($args, $this->cacheData[$this->field]);
            $this->callValidator($validator, $args);
        }
    }

    // 字典验证器
    protected function mapValidator($value, $rules)
    {
        if (is_array($rules) && !isset($rules[self::VALIDATOR_CONTAINER])) {
            if (isset($rules[self::LIST_ARRAY_MARK]) && $value != self::PARAM_NULL) {
                $rules = current($rules);
                if (preg_match('/^\d*$/', implode('', array_keys($value)))) {
                    array_map(function ($one) use ($rules) {
                        array_walk($rules, function ($item, $k) use ($one) {
                            if (isset($one[$k])) {
                                $this->mapValidator($one[$k], $item);
                            } else {
                                $this->mapValidator(self::PARAM_NULL, $item);
                            }
                        });
                    }, $value);
                } else {
                    $this->mapValidator(self::PARAM_NULL, $rules);
                }
            } else {
                foreach ($rules as $k => $v) {
                    if (isset($value[$k])) {
                        $this->mapValidator($value[$k], $v);
                    } else {
                        $this->mapValidator(self::PARAM_NULL, $v);
                    }
                }
            }
        } else if (isset($rules[self::VALIDATOR_CONTAINER])) {
            foreach ($rules[self::VALIDATOR_CONTAINER] as $validator => $args) {
                $this->field = is_array($rules[self::VALIDATOR_SYNTAX]) ? array_shift($rules[self::VALIDATOR_SYNTAX]) : $rules[self::VALIDATOR_SYNTAX];
                array_unshift($args, $value);
                $this->callValidator($validator, $args);
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

    }

}

$validator = new BidirectionalValidator();

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
    ]
], [
    'name' => 'string',
    'password' => 'string',
    'attr._.time' => 'int',
    'attr._.price' => 'string',
    'attr._.sweet._.one' => 'string',
    'attr._.sweet._.two' => 'string',
    'student.class.grad' => 'int|string'
]);