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
    const NORMAL_VALIDATION = 1;

    public function execute(array $pendingData, array $expressions)
    {
        $this->parse($expressions);
        foreach($pendingData as $field => $value) {
            $pieces = explode(self::HIERARCHY_DELIMITER, $field);
            if (empty($pieces)) {
                throw new ValidatorException('语法错误');
            }
            if (!isset($this->rules[$pieces[0]])) {
                continue;
            }
            switch(count($pieces)) {
                case self::NORMAL_VALIDATION:
                    $this->normalValidator($field, $value);
                    break;
                default:
                    $this->mapValidator($pieces, $value);
                    break;
            }
        }
        return $this;
    }

    // 字典验证器
    protected function mapValidator($pieces, $value)
    {
        $map = $this->reverse($pieces, $value);
        foreach ($this->rules[$pieces[0]] as $validator => $args) {
            if (!in_array(implode(self::HIERARCHY_DELIMITER, $pieces), $this->compactCacheData)) {
                $this->cacheData = array_merge_recursive($this->cacheData, $map);
                array_push($this->compactCacheData, implode(self::HIERARCHY_DELIMITER, $pieces));
            }
            //$this->callValidator($validator, $args);
        }

//        [
//            'attr' => [
//                'sex' => [
//                    'int' => []
//                ],
//                'name' => [
//                    'string' => []
//                ]
//            ]
//        ]

    }



    // 普通验证器
    protected function normalValidator($field, $value)
    {
        $this->field = $field;
        foreach ($this->rules[$this->field][self::VALIDATOR_CONTAINER] as $validator => $args) {
            if(!isset($this->cacheData[$this->field])) {
                $this->cacheData[$this->field] = $value;
            }
            $this->callValidator($validator, $args);
        }
    }

    public function getCode()
    {

    }

    public function getMessage()
    {

    }

    // test
    public function getCacheData()
    {
        return $this->cacheData;
    }

    public function getRules()
    {
        return $this->rules;
    }
}



$validator = new BidirectionalValidator();

$validator->execute([
    'name' => 'shuchao',
    'password' => '123',
    'attr.sex' => 'man',
    'attr.name.ljlj' => 'ts',
    'attr.page' => 'ts',
], [
    'name' => 'string',
    'password' => 'string',
    'attr.sex' => 'string',
    'attr.name.ljlj' => 'string'
]);

//var_dump($validator->getRules(), $validator->getCacheData());
