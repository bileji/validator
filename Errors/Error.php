<?php

namespace Bileji\Validator\Errors;

use Bileji\Validator\Interfaces\ErrorInterface;

class Error implements ErrorInterface
{
    protected $code = 0;

    protected $message = '';

    public function getCode()
    {
        return $this->code;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function set($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }
}