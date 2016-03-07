<?php

namespace Bileji\Validator;

interface ValidatorInterface
{

    // 语法解析
    private function parse(array $rules);
    
    // 执行验证 
    public function execute(array $expressions);
    
    // 状态码
    public function getCode();

    // 状态信息
    public function getMessage();
}
