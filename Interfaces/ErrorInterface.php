<?php

namespace Bileji\Validator\Interfaces;

interface ErrorInterface
{
    public function set($code, $message);

    public function getCode();

    public function getMessage();
}