<?php

namespace Bileji\Validator\Interfaces;

interface ValidatorInterface
{
    // 是否通过验证
    public function fails();

    // 错误信息
    public function errors();

    // 执行验证
    public function execute(array $data, array $expressions);

    // 自定义提示消息
    public function withMessage(array $messages);
}