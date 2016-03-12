<?php
namespace Bileji\Validator;

abstract class Validator extends ValidatorHeader
{
    protected function validatorMap($value)
    {
        return is_array($value) && !preg_match('/^\d*$/', implode('', array_keys($value))) ? $value : null;
    }

    protected function validatorList($value)
    {
        return is_array($value) && preg_match('/^\d*$/', implode('', array_keys($value))) ? $value : null;
    }

    protected function validatorString($value)
    {
        return is_string($value) ? $value : null;
    }

    protected function validatorNumeric($value)
    {
        return is_numeric($value) ? $value : null;
    }

    protected function validatorRequired($value)
    {
        return !empty($value) ? $value : null;
    }
}