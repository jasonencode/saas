<?php

namespace App\Support\Filesystem\Adapters;

use DateTimeInterface;
use Exception;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use OSS\Core\OssException;
use OSS\Http\RequestCore_Exception;
use OSS\OssClient;
use RuntimeException;

class OssAdapter extends CoreAdapter
{
    private OssClient $client;

    /**
     * 检查文件是否存在
     *
     * @param  string  $path  文件路径
     *
     * @throws UnableToCheckFileExistence 检查失败
     *
     * @return bool 文件是否存在
     */
    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    /**
     * 检查目录是否存在
     *
     * @param  string  $path  目录路径
     *
     * @throws UnableToCheckDirectoryExistence 检查失败
     *
     * @return bool 目录是否存在
     */
    public function directoryExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    /**
     * 写入文件内容
     *
     * @param  string  $path  文件路径
     * @param  string  $contents  文件内容
     * @param  Config  $config  配置选项
     *
     * @throws UnableToWriteFile 写入失败
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $options = $config->get('options', []);

        try {
            $this->client->putObject($this->bucket, $path, $contents, $options);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 写入文件流
     *
     * @param  string  $path  文件路径
     * @param  resource  $contents  文件流
     * @param  Config  $config  配置选项
     *
     * @throws UnableToWriteFile 写入失败
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $options = $config->get('options', []);

        try {
            $this->client->uploadStream($this->bucket, $path, $contents, $options);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 读取文件内容
     *
     * @param  string  $path  文件路径
     *
     * @throws UnableToReadFile 读取失败
     *
     * @return string 文件内容
     */
    public function read(string $path): string
    {
        try {
            return $this->client->getObject($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 读取文件流
     *
     * @param  string  $path  文件路径
     *
     * @throws UnableToReadFile 读取失败
     *
     * @return resource 文件流
     */
    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');

        try {
            fwrite($stream, $this->client->getObject($this->bucket, $path, [OssClient::OSS_FILE_DOWNLOAD => $stream]));
        } catch (Exception $exception) {
            fclose($stream);
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
        rewind($stream);

        return $stream;
    }

    /**
     * 删除目录
     *
     * @param  string  $path  目录路径
     *
     * @throws UnableToDeleteDirectory|FilesystemException 删除失败
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $contents = $this->listContents($path, false);
            $files = [];
            foreach ($contents as $i => $content) {
                if ($content instanceof DirectoryAttributes) {
                    $this->deleteDirectory($content->path());

                    continue;
                }
                $files[] = $content->path();
                if ($i && $i % 100 === 0) {
                    $this->client->deleteObjects($this->bucket, $files);
                    $files = [];
                }
            }
            !empty($files) && $this->client->deleteObjects($this->bucket, $files);
            $this->client->deleteObject($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 列出目录内容
     *
     * @param  string  $path  目录路径
     * @param  bool  $deep  是否递归列出
     *
     * @throws FilesystemException 列出失败
     *
     * @return iterable 文件和目录属性
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $nextMarker = '';
        while (true) {
            $options = [
                OssClient::OSS_PREFIX => $path,
                OssClient::OSS_MARKER => $nextMarker,
            ];

            try {
                $listObjectInfo = $this->client->listObjects($this->bucket, $options);
                $nextMarker = $listObjectInfo->getNextMarker();
            } catch (Exception $exception) {
                throw new RuntimeException($exception->getMessage(), 0, $exception);
            }

            $prefixList = $listObjectInfo->getPrefixList();
            foreach ($prefixList as $prefixInfo) {
                $subPath = $prefixInfo->getPrefix();
                if ($subPath === $path) {
                    continue;
                }
                yield new DirectoryAttributes($subPath);
                if ($deep === true) {
                    $contents = $this->listContents($subPath, true);
                    foreach ($contents as $content) {
                        yield $content;
                    }
                }
            }

            $listObject = $listObjectInfo->getObjectList();
            if (!empty($listObject)) {
                foreach ($listObject as $objectInfo) {
                    $objectPath = $objectInfo->getKey();
                    $objectLastModified = strtotime($objectInfo->getLastModified());
                    if (str_ends_with($objectPath, '/')) {
                        continue;
                    }
                    yield new FileAttributes($objectPath, $objectInfo->getSize(), null, $objectLastModified);
                }
            }

            if ($listObjectInfo->getIsTruncated() !== 'true') {
                break;
            }
        }
    }

    /**
     * 创建目录
     *
     * @param  string  $path  目录路径
     * @param  Config  $config  配置选项
     *
     * @throws UnableToCreateDirectory 创建失败
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->createObjectDir($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCreateDirectory::dueToFailure($path, $exception);
        }
    }

    /**
     * 设置文件可见性
     *
     * @param  string  $path  文件路径
     * @param  string  $visibility  可见性
     *
     * @throws UnableToSetVisibility 设置失败
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $acl = $visibility === Visibility::PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        try {
            $this->client->putObjectAcl($this->bucket, $path, $acl);
        } catch (Exception $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 获取文件可见性
     *
     * @param  string  $path  文件路径
     *
     * @throws UnableToRetrieveMetadata 获取失败
     *
     * @return FileAttributes 文件属性
     */
    public function visibility(string $path): FileAttributes
    {
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $path, []);
        } catch (Exception $exception) {
            throw UnableToRetrieveMetadata::visibility($path, $exception->getMessage());
        }

        return new FileAttributes(
            $path,
            null,
            $acl === OssClient::OSS_ACL_TYPE_PRIVATE ? Visibility::PRIVATE : Visibility::PUBLIC
        );
    }

    /**
     * 获取文件 MIME 类型
     *
     * @param  string  $path  文件路径
     *
     * @throws OssException OSS 异常
     * @throws RequestCore_Exception 请求异常
     *
     * @return FileAttributes 文件属性
     */
    public function mimeType(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if ($meta->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $meta;
    }

    /**
     * 获取文件元数据
     *
     * @param  string  $path  文件路径
     *
     * @throws OssException OSS 异常
     * @throws RequestCore_Exception 请求异常
     *
     * @return FileAttributes 文件属性
     */
    protected function getMetadata(string $path): FileAttributes
    {
        $result = $this->client->getObjectMeta($this->bucket, $path);

        $size = isset($result['content-length']) ? (int) $result['content-length'] : 0;
        $timestamp = isset($result['last-modified']) ? strtotime($result['last-modified']) : 0;
        $mimetype = $result['content-type'] ?? '';

        try {
            $acl = $this->client->getObjectAcl($this->bucket, $path, []);
            $visibility = $acl === OssClient::OSS_ACL_TYPE_PRIVATE ? Visibility::PRIVATE : Visibility::PUBLIC;
        } catch (Exception) {
            $visibility = Visibility::PUBLIC;
        }

        return new FileAttributes($path, $size, $visibility, $timestamp, $mimetype);
    }

    /**
     * 获取文件最后修改时间
     *
     * @param  string  $path  文件路径
     *
     * @throws OssException OSS 异常
     * @throws RequestCore_Exception 请求异常
     *
     * @return FileAttributes 文件属性
     */
    public function lastModified(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if ($meta->lastModified() === null) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $meta;
    }

    /**
     * 获取文件大小
     *
     * @param  string  $path  文件路径
     *
     * @throws OssException OSS 异常
     * @throws RequestCore_Exception 请求异常
     *
     * @return FileAttributes 文件属性
     */
    public function fileSize(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if ($meta->fileSize() === null) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $meta;
    }

    /**
     * 移动文件
     *
     * @param  string  $source  源路径
     * @param  string  $destination  目标路径
     * @param  Config  $config  配置选项
     *
     * @throws UnableToMoveFile 移动失败
     */
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Exception $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * 复制文件
     *
     * @param  string  $source  源路径
     * @param  string  $destination  目标路径
     * @param  Config  $config  配置选项
     *
     * @throws UnableToCopyFile 复制失败
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
        } catch (Exception $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * 删除文件
     *
     * @param  string  $path  文件路径
     *
     * @throws UnableToDeleteFile 删除失败
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 获取临时访问 URL
     *
     * @param  string  $path  文件路径
     * @param  DateTimeInterface  $expiration  过期时间
     * @param  array  $options  额外参数
     *
     * @throws OssException OSS 异常
     * @throws InvalidArgumentException 过期时间无效
     *
     * @return bool|string 临时 URL 或 false
     */
    public function getTemporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): bool|string
    {
        if (Carbon::now()->isAfter($expiration)) {
            throw new InvalidArgumentException('Expiration time must be in the future');
        }

        try {
            $url = $this->client->signUrl(
                bucket: $this->bucket,
                object: $path,
                timeout: (int) Carbon::now()->diffInSeconds($expiration),
                options: $options
            );

            if ($this->url) {
                $url = sprintf('%s%s?%s', $this->url, $path, parse_url($url, PHP_URL_QUERY));
            }

            return $url;
        } catch (Exception $e) {
            if ($this->throw) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * 初始化 OSS 客户端
     */
    protected function initClient(): void
    {
        $this->client = new OssClient($this->key, $this->secret, $this->endpoint);
    }
}
