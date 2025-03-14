<?php

namespace App\Extensions\Filesystem\Adapters;

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

class OssAdapter extends CoreAdapter
{
    private OssClient $client;

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $options = $config->get('options', []);

        try {
            $this->client->putObject($this->bucket, $path, $contents, $options);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $options = $config->get('options', []);

        try {
            $this->client->uploadStream($this->bucket, $path, $contents, $options);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            return $this->client->getObject($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

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
                if ($i && 0 == $i % 100) {
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
     * @throws FilesystemException
     * @throws Exception
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
                throw new Exception($exception->getMessage(), 0, $exception);
            }

            $prefixList = $listObjectInfo->getPrefixList();
            foreach ($prefixList as $prefixInfo) {
                $subPath = $prefixInfo->getPrefix();
                if ($subPath == $path) {
                    continue;
                }
                yield new DirectoryAttributes($subPath);
                if (true === $deep) {
                    $contents = $this->listContents($subPath, $deep);
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

            if ('true' !== $listObjectInfo->getIsTruncated()) {
                break;
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->createObjectDir($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToCreateDirectory::dueToFailure($path, $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $acl = Visibility::PUBLIC === $visibility ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        try {
            $this->client->putObjectAcl($this->bucket, $path, $acl);
        } catch (Exception $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

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
            OssClient::OSS_ACL_TYPE_PRIVATE === $acl ? Visibility::PRIVATE : Visibility::PUBLIC
        );
    }

    /**
     * @throws OssException
     * @throws RequestCore_Exception
     */
    public function mimeType(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if (null === $meta->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $meta;
    }

    /**
     * @throws OssException
     * @throws RequestCore_Exception
     */
    protected function getMetadata(string $path): FileAttributes
    {
        $result = $this->client->getObjectMeta($this->bucket, $path);

        $size = isset($result['content-length']) ? intval($result['content-length']) : 0;
        $timestamp = isset($result['last-modified']) ? strtotime($result['last-modified']) : 0;
        $mimetype = $result['content-type'] ?? '';

        try {
            $acl = $this->client->getObjectAcl($this->bucket, $path, []);
            $visibility = OssClient::OSS_ACL_TYPE_PRIVATE === $acl ? Visibility::PRIVATE : Visibility::PUBLIC;
        } catch (Exception) {
            $visibility = Visibility::PUBLIC;
        }

        return new FileAttributes($path, $size, $visibility, $timestamp, $mimetype);
    }

    /**
     * @throws OssException
     * @throws RequestCore_Exception
     */
    public function lastModified(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if (null === $meta->lastModified()) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $meta;
    }

    /**
     * @throws OssException
     * @throws RequestCore_Exception
     */
    public function fileSize(string $path): FileAttributes
    {
        $meta = $this->getMetadata($path);
        if (null === $meta->fileSize()) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $meta;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Exception $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
        } catch (Exception $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($this->bucket, $path);
        } catch (Exception $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * 获取临时访问URL
     *
     * @param  string             $path        文件路径
     * @param  DateTimeInterface  $expiration  过期时间
     * @param  array              $options     额外参数
     * @return bool|string
     * @throws OssException
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

    protected function initClient(): void
    {
        $this->client = new OssClient($this->key, $this->secret, $this->endpoint);
    }
}
