<?php

namespace App\Services\System;

use App\Contracts\ServiceInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SecretEncryptionService implements ServiceInterface
{
    /**
     * 需要加密的字段（表 => 字段列表）
     *
     * 仅包含从不出现在查询条件、索引中的密钥字段；
     * app_key / app_id / mch_id 等标识列保持明文。
     *
     * @var array<string, array<int, string>>
     */
    public const array TARGETS = [
        'tenants' => ['app_secret'],
        'socialite_accounts' => ['app_secret'],
        'aliyuns' => ['app_secret'],
        'wechats' => ['app_secret'],
        'wechat_minis' => ['app_secret'],
        'wechat_payments' => ['secret', 'private_key'],
        'alipays' => ['private_key'],
        'socialites' => ['access_token', 'refresh_token'],
    ];

    /**
     * 将存量明文密钥加密回写（幂等，已加密的值会跳过）
     *
     * @return array<string, int> 各表加密的记录数
     */
    public function encryptAll(): array
    {
        return $this->convert(true);
    }

    /**
     * 将密钥解密回写为明文（回滚用，幂等）
     *
     * @return array<string, int> 各表解密的记录数
     */
    public function decryptAll(): array
    {
        return $this->convert(false);
    }

    /**
     * 判断值是否已是加密串
     *
     * @param  string  $value  字段值
     */
    public function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    /**
     * 按目标表逐条转换字段值
     *
     * @param  bool  $encrypt  true 加密，false 解密
     *
     * @return array<string, int> 各表处理的记录数
     */
    private function convert(bool $encrypt): array
    {
        $counts = [];

        foreach (self::TARGETS as $table => $columns) {
            $counts[$table] = 0;

            DB::table($table)
                ->select(['id', ...$columns])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $columns, $encrypt, &$counts): void {
                    foreach ($rows as $row) {
                        $updates = $this->buildUpdates($row, $columns, $encrypt);

                        if ($updates !== []) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                            $counts[$table]++;
                        }
                    }
                });
        }

        return $counts;
    }

    /**
     * 构造单行需要更新的字段
     *
     * @param  object  $row  数据行
     * @param  array<int, string>  $columns  字段列表
     * @param  bool  $encrypt  true 加密，false 解密
     *
     * @return array<string, string> 需要更新的字段
     */
    private function buildUpdates(object $row, array $columns, bool $encrypt): array
    {
        $updates = [];

        foreach ($columns as $column) {
            $value = $row->{$column} ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $value = (string) $value;

            // 已经是目标状态时跳过，保证命令可重复执行
            if ($this->isEncrypted($value) === $encrypt) {
                continue;
            }

            $updates[$column] = $encrypt ? Crypt::encryptString($value) : Crypt::decryptString($value);
        }

        return $updates;
    }
}
