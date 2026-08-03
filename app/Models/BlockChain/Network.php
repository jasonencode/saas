<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\ChainType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\BlockChain\NetworkPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
#[UsePolicy(NetworkPolicy::class)]
class Network extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ChainType::class,
            'config' => AsArrayObject::class,
        ];
    }

    /**
     * 获取 FISCO BCOS 群组 ID
     *
     * @return string 群组 ID
     */
    public function getGroupId(): string
    {
        return $this->config['fisco']['group_id'] ?? 'group0';
    }

    /**
     * 获取 SSL 连接选项
     *
     * @return array<string, mixed> SSL 选项
     */
    public function getSslOptions(): array
    {
        $options = [];

        $fisco = $this->config['fisco'] ?? [];

        if (!empty($fisco['ca_cert'])) {
            $options['verify'] = $this->writeTempCert($fisco['ca_cert'], 'ca');
        }

        if (!empty($fisco['client_cert'])) {
            $options['cert'] = $this->writeTempCert($fisco['client_cert'], 'client');
        }

        if (!empty($fisco['client_key'])) {
            $options['ssl_key'] = $this->writeTempCert($fisco['client_key'], 'key');
        }

        return $options;
    }

    private function writeTempCert(string $pem, string $prefix): string
    {
        $path = storage_path(sprintf('app/certs/%s_%d_%s.pem', $prefix, $this->id, uniqid('', true)));

        if (!is_dir(dirname($path)) && !mkdir($concurrentDirectory = dirname($path), 0755, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        file_put_contents($path, $pem);

        return $path;
    }
}
