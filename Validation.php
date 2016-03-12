<?php

namespace Bileji\Validator;

use ReflectionMethod;

class Validation extends Validator implements ValidatorInterface
{
    protected function reverse(array $keys, $value, $eval = '$map', $map = [])
    {
        array_map(function ($key) use (&$eval) {
            $eval .= '[\'' . (is_numeric($key) ? intval($key) : $key) . '\']';
        }, $keys);
        eval($eval . '=' . (empty($value) ? '[]' : '\'' . $value . '\'') . ';');
        return $map;
    }

    // 验证器名称
    protected function setValidatorName($validator)
    {
        return $this->validatorName = self::VALIDATOR_CONTAINER . ucfirst(strval($validator));
    }

    protected function getValidatorName()
    {
        return strpos($this->validatorName, self::VALIDATOR_CONTAINER) === 0 ? str_replace(self::VALIDATOR_CONTAINER, '', strtolower($this->validatorName)) : '';
    }

    protected function syntaxPush(&$syntax, $field)
    {
        return $syntax .= self::HIERARCHY_DELIMITER . $field;
    }

    protected function syntaxPop(&$syntax)
    {
        $pieces = explode(self::HIERARCHY_DELIMITER, $syntax);
        array_pop($pieces);
        return $syntax = implode(self::HIERARCHY_DELIMITER, $pieces);
    }

    // 调取验证器
    protected function callValidator($validator, $args, $syntax)
    {
        if (method_exists($this, $this->setValidatorName($validator))) {
            $value = call_user_func_array([$this, $this->setValidatorName($validator)], $args);
            # 结果为空的数据视为不通过验证
            if (!empty($value)) {
                $this->reverse(explode(self::HIERARCHY_DELIMITER, $syntax), $value, '$this->cacheData', $this->cacheData);
            } else {
                $this->assembleCustomMessage($args);
            }
        } else {
            throw new ValidatorException('验证器' . $validator . '缺失');
        }
    }

    // 语法解析
    protected function parse(array $expressions)
    {
        array_walk($expressions, function ($expression, $field) {
            $validators = is_array($expression) ? $expression : explode(self::VALIDATOR_DELIMITER, $expression);
            array_map(function ($validator) use ($field) {
                $validatorAndParameters = explode(self::VALIDATOR_OF_PARAMETERS_DELIMITER, $validator);
                if (strpos($field, self::HIERARCHY_DELIMITER) !== false) {
                    $rule = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_CONTAINER . self::HIERARCHY_DELIMITER . array_shift($validatorAndParameters)), array_pop($validatorAndParameters));
                    $syntax = $this->reverse(explode(self::HIERARCHY_DELIMITER, $field . self::HIERARCHY_DELIMITER . self::VALIDATOR_SYNTAX), $field);
                    $this->rules = array_merge_recursive($this->rules, $rule, $syntax);
                } else {
                    $this->rules[$field][self::VALIDATOR_CONTAINER][array_shift($validatorAndParameters)] = explode(self::PARAMETERS_DELIMITER, array_pop($validatorAndParameters));
                }
            }, $validators);
        });
    }

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

    // 组装用户自定义消息
    protected function assembleCustomMessage($args)
    {
        $message = str_replace(':field', $this->field, $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_MESSAGE_LABEL]);
        $reflection = new ReflectionMethod(get_class($this), $this->validatorName);
        $parameters = $reflection->getParameters();
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(':' . $parameter->getName(), is_object($args[$index]) || is_array($args[$index]) ? var_export($args[$index], true) : $args[$index], $message);
        }
        $this->errorMessages[$this->field][] = [
            self::VALIDATOR_MESSAGE_LABEL => $message,
            self::VALIDATOR_CODE_LABEL => $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_CODE_LABEL],
        ];
    }

    public function withMessage()
    {

    }

    public function getCode()
    {

    }

    public function getMessage()
    {
        return $this->errorMessages;
    }

}