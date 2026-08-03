<?php

namespace App\Support\Filesystem\Adapters;

use DateTimeInterface;
use InvalidArgumentException;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

/**
 * 存储适配器基类
 *
 * 封装 AccessKey、密钥、地区、存储桶等通用配置，
 * 并提供文件 URL 与临时访问 URL 的统一接口。
 */
abstract class CoreAdapter implements FilesystemAdapter
{
    /**
     * AccessKey
     */
    protected string $key;

    /**
     * 密钥
     */
    protected string $secret;

    /**
     * 地区
     */
    protected string $region;

    /**
     * 存储桶
     */
    protected string $bucket;

    /**
     * 节点地址
     */
    protected string $endpoint;

    /**
     * CDN 地址
     */
    protected ?string $url = null;

    /**
     * 抛出原始异常
     */
    protected bool $throw;

    /**
     * 创建存储适配器实例
     *
     * @param  array  $config  配置选项
     */
    public function __construct(protected array $config)
    {
        $this->initProperties($config);
        $this->initClient();
    }

    /**
     * 初始化配置属性
     *
     * @param  array  $config  配置选项
     *
     * @throws InvalidArgumentException 配置项缺失或格式错误
     */
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

    /**
     * 初始化存储客户端
     */
    abstract protected function initClient(): void;

    /**
     * 获取文件 URL
     *
     * @param  string  $path  文件路径
     *
     * @return string 文件 URL
     */
    public function getUrl(string $path): string
    {
        if ($this->url) {
            return $this->url.$path;
        }

        return sprintf(
            '%s://%s.%s/%s',
            parse_url($this->endpoint, PHP_URL_SCHEME),
            $this->bucket,
            parse_url($this->endpoint, PHP_URL_HOST),
            $path
        );
    }

    /**
     * 获取临时访问 URL
     *
     * @param  string  $path  文件路径
     * @param  DateTimeInterface  $expiration  过期时间
     * @param  array  $options  额外参数
     *
     * @return bool|string 临时 URL 或 false
     */
    abstract public function getTemporaryUrl(
        string $path,
        DateTimeInterface $expiration,
        array $options = []
    ): bool|string;

    /**
     * 获取文件元数据
     *
     * @param  string  $path  文件路径
     *
     * @return FileAttributes 文件属性
     */
    abstract protected function getMetadata(string $path): FileAttributes;
}
