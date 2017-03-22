<?php
namespace Bileji\Validator;

use ReflectionMethod;
use Bileji\Validator\Errors\Error;
use Bileji\Validator\Errors\ValidatorErrors;
use Bileji\Validator\Exception\ValidatorException;
use Bileji\Validator\Interfaces\ValidatorInterface;

class Validator extends ValidatorHeader implements ValidatorInterface
{
    public function __construct()
    {
        $this->fail = false;
        if (!$this->error instanceof Error) {
            $this->error = new Error();
        }
        if (!$this->errors instanceof ValidatorErrors) {
            $this->errors = new ValidatorErrors();
        }
    }

    public function fails()
    {
        return $this->fail;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function getData()
    {
        return $this->cacheData;
    }

    // 反向构造数据
    protected function reverse($key, $value, &$array = [])
    {
        if (strpos($key, static::HIERARCHY_DELIMITER) === false) {
            $array[$key] = $value;
        } else {
            $keys = explode(static::HIERARCHY_DELIMITER, $key);
            $optTree = &$array;
            while (!is_null($key = array_shift($keys))) {
                if (!empty($keys)) {
                    (!isset($optTree[$key]) || !is_array($optTree[$key])) && $optTree[$key] = [];
                    $optTree = &$optTree[$key];
                } else {
                    $optTree[$key] = $value;
                }
            }
        }
        return $array;
    }

    // 设置验证器名称
    protected function setValidatorName($validator)
    {
        return $this->validatorName = self::VALIDATOR_CONTAINER . ucfirst(strval($validator));
    }

    // 获取验证器名称
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

    // 自定义消息
    public function withMessage(array $messages)
    {
        foreach ($messages as $field => $error) {
            $pieces = explode(self::VALIDATOR_DELIMITER, $field);
            switch (count($pieces)) {
                case 1:
                    if (is_string($error)) {
                        $this->defaultMessagesTemplate[$pieces[0]][self::VALIDATOR_MESSAGE_LABEL] = $error;
                    } else {
                        $this->defaultMessagesTemplate[$pieces[0]] = array_replace($this->defaultMessagesTemplate[$pieces[0]], $error);
                    }
                    break;
                case 2:
                    if (is_string($error)) {
                        $this->defaultMessagesTemplate[$pieces[0]][$pieces[1]][self::VALIDATOR_MESSAGE_LABEL] = $error;
                    } else {
                        $this->customMessages[$pieces[0]][$pieces[1]] = array_replace($this->customMessages[$pieces[0]][$pieces[1]], $error);
                    }
                    break;
            }
        }
        return $this;
    }

    // 调取验证器
    protected function callValidator($validator, $args, $syntax)
    {
        if (method_exists($this, $this->setValidatorName($validator))) {
            $value = call_user_func_array([$this, $this->setValidatorName($validator)], $args);
            # 结果为空的数据视为不通过验证
            if (!is_null($value) && $value != self::VALIDATOR_BREAK_MD5) {
                $this->reverse($syntax, $value, $this->cacheData);
            } else {
                if (!(empty($args[0]) && is_null($args[0]) && is_null($value)) || $value == self::VALIDATOR_BREAK_MD5) {
                    $this->assembleCustomMessage($args);
                }
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
                    $validatorName = array_shift($validatorAndParameters);
                    $params = [];
                    if (count($validatorAndParameters)) {
                        $params = array_pop($validatorAndParameters);
                        if (is_string($params) && strpos($params, static::PARAMETERS_DELIMITER) !== false) {
                            $params = explode(static::PARAMETERS_DELIMITER, $params);
                        }
                    }
                    $rule = $this->reverse($field . self::HIERARCHY_DELIMITER . self::VALIDATOR_CONTAINER . self::HIERARCHY_DELIMITER . $validatorName, $params);
                    $syntax = $this->reverse($field . self::HIERARCHY_DELIMITER . self::VALIDATOR_SYNTAX, $field);
                    $this->rules = array_merge_recursive($this->rules, $rule, $syntax);
                } else {
                    $this->rules[$field][self::VALIDATOR_CONTAINER][array_shift($validatorAndParameters)] = explode(self::PARAMETERS_DELIMITER, array_pop($validatorAndParameters));
                }
            }, $validators);
        });
    }
    
    //自动赋值
    protected function autoAssign(array $keys, array &$data)
    {
        $validator = $this;
        array_map(function ($key) use (&$data, $validator) {
            $tempV = $data;
            array_map(function ($item) use (&$tempV, $data) {
                $item == static::LIST_ARRAY_MARK && $item = 0;
                $tempV = !isset($tempV[$item]) ? [] : $tempV[$item];
            }, explode(static::HIERARCHY_DELIMITER, $key));
            empty($tempV) && is_array($tempV) && $validator->reverse($key, null, $data);
        }, $keys);
    }
    
    // 执行验证
    public function execute(array $pendingData, array $expressions)
    {
        $this->parse($expressions); 
        $this->autoAssign(array_keys($expressions), $pendingData);
        
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
                    foreach ($value as $n => $one) {
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
                    foreach ($value as $k => $v) {
                        if (isset($rules[$k])) {
                            $this->mapValidator($v, $rules[$k], $syntax . self::HIERARCHY_DELIMITER . $k);
                        }
                    }
                }
            } else {
                foreach ($rules as $k => $v) {
                    $this->syntaxPush($syntax, $k);
                    $this->mapValidator(self::PARAM_NULL, $v, $syntax);
                    $this->syntaxPop($syntax);
                }
            }
        } else if (isset($rules[self::VALIDATOR_CONTAINER])) {
            foreach ($rules[self::VALIDATOR_CONTAINER] as $validator => $args) {
                $this->field = is_array($rules[self::VALIDATOR_SYNTAX]) ? array_shift($rules[self::VALIDATOR_SYNTAX]) : $rules[self::VALIDATOR_SYNTAX];
                $args = is_array($args) ? $args : [$args];
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
        if (!empty($this->customMessages[$this->field])) {
            $code = $this->customMessages[$this->field][$this->getValidatorName()][self::VALIDATOR_CODE_LABEL];
            $message = $this->customMessages[$this->field][$this->getValidatorName()][self::VALIDATOR_MESSAGE_LABEL];
        } else {
            $code = $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_CODE_LABEL];
            $message = $this->defaultMessagesTemplate[$this->getValidatorName()][self::VALIDATOR_MESSAGE_LABEL];
        }
        $message = str_replace(':field', $this->field, $message);
        $reflection = new ReflectionMethod(get_class($this), $this->validatorName);
        $parameters = $reflection->getParameters();
        foreach ($parameters as $index => $parameter) {
            $message = str_replace(':' . $parameter->getName(), !is_scalar($args[$index]) ? var_export($args[$index], true) : $args[$index], $message);
        }
        $error = clone $this->error;
        $error->set($code, $message);
        $this->errors->push($this->field, $error);
        $this->fail = $this->fail || true;
    }
}
