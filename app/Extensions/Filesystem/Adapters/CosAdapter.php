<?php

namespace App\Extensions\Filesystem\Adapters;

use DateTimeInterface;
use Exception;
use Illuminate\Support\Carbon;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
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
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\CosException;

class CosAdapter extends CoreAdapter
{
    private Client $client;

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist(
                $this->bucket,
                $path
            );
        } catch (Exception $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $result = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $path,
                'MaxKeys' => 1,
            ]);

            return !empty($result['Contents']);
        } catch (Exception $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
            ]);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
            ]);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            $response = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (string) $response['Body'];
        } catch (Exception $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');

        try {
            $response = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            fwrite($stream, (string) $response['Body']);
            rewind($stream);

            return $stream;
        } catch (Exception $exception) {
            fclose($stream);
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $contents = $this->listContents($path, true);
            $objects = [];

            foreach ($contents as $content) {
                $objects[] = ['Key' => $content->path()];
            }

            if (!empty($objects)) {
                $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Objects' => $objects,
                ]);
            }

            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
        } catch (Exception $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * @throws Exception
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $marker = '';

        while (true) {
            $options = [
                'Bucket' => $this->bucket,
                'Prefix' => $path,
                'Marker' => $marker,
                'MaxKeys' => 1000,
            ];

            if (!$deep) {
                $options['Delimiter'] = '/';
            }

            try {
                $response = $this->client->listObjects($options);
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }

            foreach ($response['CommonPrefixes'] ?? [] as $prefix) {
                $subPath = $prefix['Prefix'];
                yield new DirectoryAttributes($subPath);
            }

            foreach ($response['Contents'] ?? [] as $file) {
                $filePath = $file['Key'];
                if ($filePath === $path || !$filePath) {
                    continue;
                }

                yield new FileAttributes(
                    $filePath,
                    $file['Size'],
                    null,
                    strtotime($file['LastModified'])
                );
            }

            if (!$response['IsTruncated']) {
                break;
            }

            $marker = $response['NextMarker'];
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => '',
            ]);
        } catch (Exception $exception) {
            throw UnableToCreateDirectory::dueToFailure($path, $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $acl = $visibility === Visibility::PUBLIC ? 'public-read' : 'private';

        try {
            $this->client->putObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ACL' => $acl,
            ]);
        } catch (Exception $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $result = $this->client->getObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $visibility = Visibility::PRIVATE;
            foreach ($result['Grants'] as $grant) {
                if (
                    isset($grant['Grantee']['URI']) &&
                    $grant['Grantee']['URI'] === 'http://cam.qcloud.com/groups/global/AllUsers' &&
                    $grant['Permission'] === 'READ'
                ) {
                    $visibility = Visibility::PUBLIC;
                    break;
                }
            }

            return new FileAttributes($path, null, $visibility);
        } catch (Exception $exception) {
            throw UnableToRetrieveMetadata::visibility($path, $exception->getMessage(), $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        $attributes = $this->getMetadata($path);

        if ($attributes->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    protected function getMetadata(string $path): FileAttributes
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $visibility = null;
            try {
                $acl = $this->client->getObjectAcl([
                    'Bucket' => $this->bucket,
                    'Key' => $path,
                ]);

                foreach ($acl['Grants'] as $grant) {
                    if (
                        isset($grant['Grantee']['URI']) &&
                        $grant['Grantee']['URI'] === 'http://cam.qcloud.com/groups/global/AllUsers' &&
                        $grant['Permission'] === 'READ'
                    ) {
                        $visibility = Visibility::PUBLIC;
                        break;
                    }
                }
                $visibility = $visibility ?: Visibility::PRIVATE;
            } catch (Exception) {
                $visibility = Visibility::PRIVATE;
            }

            return new FileAttributes(
                $path,
                $result['ContentLength'] ?? null,
                $visibility,
                strtotime($result['LastModified']),
                $result['ContentType'] ?? null
            );
        } catch (Exception $exception) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $exception->getMessage(), $exception);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        $attributes = $this->getMetadata($path);

        if ($attributes->lastModified() === null) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $attributes;
    }

    public function fileSize(string $path): FileAttributes
    {
        $attributes = $this->getMetadata($path);

        if ($attributes->fileSize() === null) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $attributes;
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
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $destination,
                'CopySource' => urlencode($this->bucket.'.'.$this->endpoint.'/'.$source),
            ]);
        } catch (Exception $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
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
     * @throws CosException|Exception
     */
    public function getTemporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): bool|string
    {
        try {
            $url = $this->client->getObjectUrl(
                $this->bucket,
                $path,
                Carbon::now()->diffInSeconds($expiration),
                $options
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
        $this->client = new Client([
            'region' => $this->region,
            'credentials' => [
                'secretId' => $this->key,
                'secretKey' => $this->secret,
            ],
            'schema' => 'https',
        ]);
    }
}
