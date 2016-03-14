<?php

namespace Bileji\Validator\Interfaces;

use Bileji\Validator\Error\Error;

interface ValidatorErrorsInterface
{
    public function all();

    public function first();

    public function get($field);

    public function has($field);

    public function push($field, Error $error);
}