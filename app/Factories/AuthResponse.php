<?php

namespace App\Factories;

use App\Models\User;
use JsonSerializable;

class AuthResponse implements JsonSerializable
{
    protected array $result;

    protected string $tokenName = 'API';

    public function __construct(protected User $user)
    {
        $this->result = [
            'token' => $user->createToken($this->tokenName)->plainTextToken,
            'type' => 'Bearer',
            'key' => 'Authorization',
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->result;
    }

    public function __toString(): string
    {
        return json_encode($this->result, JSON_UNESCAPED_UNICODE) ?: '';
    }
}