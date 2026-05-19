<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage;

use MTZ\Toolkit\Storage\Contracts\DocumentStorageInterface;
use MTZ\Toolkit\Storage\Exceptions\StorageException;
use MTZ\Toolkit\Storage\Support\PathNormalizer;

final readonly class LocalDocumentStorage implements DocumentStorageInterface
{
    public function __construct(
        private string $basePath,
    ) {
    }

    public function put(string $path, string $content): void
    {
        $path = PathNormalizer::normalize($path);
        $fullPath = $this->fullPath($path);
        $directory = dirname($fullPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory))
        {
            throw StorageException::cannotWrite($fullPath);
        }

        if (file_put_contents($fullPath, $content) === false)
        {
            throw StorageException::cannotWrite($fullPath);
        }
    }

    public function get(string $path): string
    {
        $path = PathNormalizer::normalize($path);

        $contents = @file_get_contents($this->fullPath($path));

        if ($contents === false)
        {
            throw StorageException::cannotRead($path);
        }

        return $contents;
    }

    public function exists(string $path): bool
    {
        return is_file($this->fullPath(PathNormalizer::normalize($path)));
    }

    public function delete(string $path): void
    {
        $path = PathNormalizer::normalize($path);
        $fullPath = $this->fullPath($path);

        if (! is_file($fullPath))
        {
            return;
        }

        if (! @unlink($fullPath))
        {
            throw StorageException::cannotDelete($path);
        }
    }

    private function fullPath(string $path): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }
}
