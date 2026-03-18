<?php

namespace App\Contracts;

use App\Models\Voucher;

class SettleTaskData
{
    public array $parameters;

    /**
     * @param  Voucher  $voucher  结算凭据
     * @param  array  $parameters  所有参数，可以传递给下一步用
     */
    public function __construct(public Voucher $voucher, array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function addParameter(array $parameter): void
    {
        $this->parameters = array_merge($this->parameters, $parameter);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
