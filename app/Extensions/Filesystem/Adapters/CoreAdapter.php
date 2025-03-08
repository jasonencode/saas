<?php

namespace App\Extensions\Filesystem\Adapters;

use DateTimeInterface;
use InvalidArgumentException;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

abstract class CoreAdapter implements FilesystemAdapter
{
    /**
     * AccessKey
     *
     * @var string
     */
    protected string $key;

    /**
     * 密钥
     *
     * @var string
     */
    protected string $secret;

    /**
     * 地区
     *
     * @var string
     */
    protected string $region;

    /**
     * 存储桶
     *
     * @var string
     */
    protected string $bucket;

    /**
     * 节点地址
     *
     * @var string
     */
    protected string $endpoint;

    /**
     * CDN 地址
     *
     * @var string|null
     */
    protected ?string $url = null;

    /**
     * 抛出原始异常
     *
     * @var bool
     */
    protected bool $throw;

    public function __construct(protected array $config)
    {
        $this->initProperties($config);
        $this->initClient();
    }

    protected function initProperties(array $config): void
    {
        $requiredKeys = ['key', 'secret', 'region', 'bucket', 'endpoint', 'url', 'throw'];
        $missingKeys = array_diff($requiredKeys, array_keys($config));

        if (!empty($missingKeys)) {
            throw new InvalidArgumentException(
                sprintf('Missing required configuration keys: %s', implode(', ', $missingKeys))
            );
        }

        // 验证字符串类型的配置项
        $stringKeys = ['key', 'secret', 'region', 'bucket', 'endpoint'];
        foreach ($stringKeys as $key) {
            if (!is_string($config[$key])) {
                throw new InvalidArgumentException(
                    sprintf('Storage configuration key "%s" must be a string', $key)
                );
            }

            if (trim($config[$key]) === '') {
                throw new InvalidArgumentException(
                    sprintf('Storage configuration key "%s" cannot be empty', $key)
                );
            }
        }

        if (!is_bool($config['throw'])) {
            throw new InvalidArgumentException(
                'Storage configuration key "throw" must be a boolean'
            );
        }

        if (!filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid endpoint URL format');
        }

        if (!empty($config['url']) && !filter_var($config['url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid CDN host URL format');
        }

        $this->key = $config['key'];
        $this->secret = $config['secret'];
        $this->region = $config['region'];
        $this->bucket = $config['bucket'];
        $this->endpoint = rtrim($config['endpoint'], '/');
        $this->url = $config['url'] ? rtrim($config['url'], '/').'/' : null;
        $this->throw = $config['throw'];
    }

    abstract protected function initClient(): void;

    public function getUrl(string $path): string
    {
        if ($this->url) {
            return $this->url.$path;
        } else {
            return sprintf(
                '%s://%s.%s/%s',
                parse_url($this->endpoint, PHP_URL_SCHEME),
                $this->bucket,
                parse_url($this->endpoint, PHP_URL_HOST),
                $path
            );
        }
    }

    abstract public function getTemporaryUrl(
        string $path,
        DateTimeInterface $expiration,
        array $options = []
    ): bool|string;

    abstract protected function getMetadata(string $path): FileAttributes;
}
