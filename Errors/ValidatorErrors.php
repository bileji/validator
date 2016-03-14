<?php

namespace Bileji\Validator\Errors;

use Bileji\Validator\Interfaces\ValidatorErrorsInterface;

class ValidatorErrors implements ValidatorErrorsInterface
{
    protected $errors = [];

    public function all()
    {
        return $this->errors;
    }

    public function first()
    {
        return array_shift(array_shift($this->errors));
    }

    public function get($field)
    {
        return $this->errors[$field];
    }

    public function has($field)
    {
        return empty($this->errors[$field]);
    }

    public function push($field, Error $error)
    {
        if (empty($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        return array_push($this->errors[$field], $error);
    }
}