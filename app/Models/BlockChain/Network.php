<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\ChainType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\BlockChain\NetworkPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
#[UsePolicy(NetworkPolicy::class)]
class Network extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => ChainType::class,
        'group_id' => 'integer',
        'ca_cert' => 'string',
        'client_cert' => 'string',
        'client_key' => 'string',
    ];

    /**
     * 获取 SSL 连接选项（用于 mTLS）
     *
     * @return array<string, string>
     */
    public function getSslOptions(): array
    {
        $options = [];

        if ($this->ca_cert) {
            $options['verify'] = $this->writeTempCert($this->ca_cert, 'ca');
        }

        if ($this->client_cert) {
            $options['cert'] = $this->writeTempCert($this->client_cert, 'client');
        }

        if ($this->client_key) {
            $options['ssl_key'] = $this->writeTempCert($this->client_key, 'key');
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
