<?php

namespace App\Console\Commands\System;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Services\System\SecretEncryptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('system:decrypt-secrets')]
#[Description('把密钥字段解密回写为明文（回滚用，幂等）')]
#[CommandLabel('解密密钥字段')]
class DecryptSecretsCommand extends BaseCommand
{
    public function handle(): int
    {
        $counts = service(SecretEncryptionService::class)->decryptAll();

        foreach ($counts as $table => $count) {
            $this->line(sprintf('%s: %d 条', $table, $count));
        }

        $this->info(sprintf('完成，共处理 %d 条记录。', array_sum($counts)));

        $this->logContext(['completed' => array_sum($counts)]);

        return self::SUCCESS;
    }
}
