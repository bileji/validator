<?php

namespace Bileji\Validator;

interface ValidatorInterface
{
    // 执行验证 
    public function execute(array $data, array $expressions);

    // 状态码
    public function getCode();

    // 状态信息
    public function getMessage();

    // 自定义提示消息
    public function withMessage();
}
