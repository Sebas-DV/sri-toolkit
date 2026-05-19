<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use DateTimeInterface;
use MTZ\Toolkit\Storage\Contracts\TemporaryUrlDocumentStorageInterface;
use MTZ\Toolkit\Storage\Exceptions\StorageException;
use MTZ\Toolkit\Storage\Support\PathNormalizer;
use Psr\Http\Message\StreamInterface;

final readonly class S3DocumentStorage implements TemporaryUrlDocumentStorageInterface
{
    public function __construct(
        private S3Client $client,
        private string $bucket,
        private string $prefix = '',
    ) {
    }

    public function put(string $path, string $content): void
    {
        $path = PathNormalizer::normalize($path);

        try
        {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
                'Body' => $content,
            ]);
        } catch (AwsException $e)
        {
            throw StorageException::cannotWrite($path, $e);
        }
    }

    public function get(string $path): string
    {
        $path = PathNormalizer::normalize($path);

        try
        {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
            ]);

            $body = $result['Body'] ?? null;

            if ($body instanceof StreamInterface)
            {
                return $body->getContents();
            }

            if (is_string($body))
            {
                return $body;
            }

            throw StorageException::cannotRead($path);
        } catch (AwsException $e)
        {
            throw StorageException::cannotRead($path, $e);
        }
    }

    public function exists(string $path): bool
    {
        $path = PathNormalizer::normalize($path);

        return $this->client->doesObjectExist(
            $this->bucket,
            $this->key($path),
        );
    }

    public function delete(string $path): void
    {
        $path = PathNormalizer::normalize($path);

        try
        {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->key($path),
            ]);
        } catch (AwsException $e)
        {
            throw StorageException::cannotDelete($path, $e);
        }
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt): string
    {
        $path = PathNormalizer::normalize($path);

        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $this->key($path),
        ]);

        $request = $this->client->createPresignedRequest($command, $expiresAt);

        return (string) $request->getUri();
    }

    private function key(string $path): string
    {
        $prefix = trim($this->prefix, '/');

        if ($prefix === '')
        {
            return $path;
        }

        return $prefix . '/' . $path;
    }
}
