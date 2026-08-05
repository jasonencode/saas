<?php

namespace App\Http\Responses;

use App\Models\User\User;
use JsonException;
use JsonSerializable;

class AuthResponse implements JsonSerializable
{
    protected array $result;

    protected string $tokenName = 'API';

    /**
     * 构造函数
     *
     * @param  User  $user  用户模型
     */
    public function __construct(protected User $user)
    {
        $this->result = [
            'token' => $user->createToken($this->tokenName)->plainTextToken,
            'type' => 'Bearer',
            'key' => 'Authorization',
        ];
    }

    /**
     * JSON序列化
     *
     * @return array 序列化后的数组
     */
    public function jsonSerialize(): array
    {
        return $this->result;
    }

    /**
     * 转换为字符串
     *
     *
     * @throws JsonException JSON编码失败时
     *
     * @return string JSON字符串
     */
    public function __toString(): string
    {
        return json_encode($this->result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
