<?php

namespace Bileji\Validator\Test;

// require for test
require "../ValidatorInterface.php";
require "../ValidatorException.php";
require "../ValidatorHeader.php";
require "../Validator.php";
require "../Validation.php";

use Bileji\Validator\Validation;

class PositiveValidator extends Validation
{
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