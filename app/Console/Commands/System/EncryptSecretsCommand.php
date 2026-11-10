<?php

namespace App\Console\Commands\System;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Services\System\SecretEncryptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('system:encrypt-secrets')]
#[Description('加密数据库中的存量密钥字段（幂等）')]
#[CommandLabel('加密存量密钥')]
class EncryptSecretsCommand extends BaseCommand
{
    public function handle(): int
    {
        $counts = service(SecretEncryptionService::class)->encryptAll();

        foreach ($counts as $table => $count) {
            $this->line(sprintf('%s: %d 条', $table, $count));
        }

        $this->info(sprintf('完成，共处理 %d 条记录。', array_sum($counts)));

        $this->logContext(['completed' => array_sum($counts)]);

        return self::SUCCESS;
    }
}
