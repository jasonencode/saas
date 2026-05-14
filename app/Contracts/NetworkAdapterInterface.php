<?php

namespace App\Contracts;

interface NetworkAdapterInterface
{
    public function generatePrivateKey(): string;

    public function validatePrivateKey(string $privateKey): bool;

    public function getPublicKeyFromPrivateKey(string $privateKey): string;

    public function getAddressFromPublicKey(string $publicKey): string;

    public function getAddressFromPrivateKey(string $privateKey): string;

}
