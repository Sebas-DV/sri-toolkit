<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Storage;

use MTZ\Toolkit\Storage\Contracts\DocumentStorageInterface;
use MTZ\Toolkit\Storage\Exceptions\StorageException;

/**
 * In-memory document storage double for exercising storage consumers in tests.
 */
final class InMemoryDocumentStorage implements DocumentStorageInterface
{
    /**
     * @var array<string, string> Stored contents keyed by path.
     */
    public array $files = [];

    public function put(string $path, string $content): void
    {
        $this->files[$path] = $content;
    }

    public function get(string $path): string
    {
        if (! array_key_exists($path, $this->files))
        {
            throw StorageException::cannotRead($path);
        }

        return $this->files[$path];
    }

    public function exists(string $path): bool
    {
        return array_key_exists($path, $this->files);
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }
}
