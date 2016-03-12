<?php

namespace Bileji\Validator\Test;

// require for test
require "../ValidatorInterface.php";
require "../ValidatorException.php";
require "../ValidatorHeader.php";
require "../Validator.php";
require "../Validation.php";

use Bileji\Validator\Validation;

class ReverseValidator extends Validation
{

}

$validator = new ReverseValidator();

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