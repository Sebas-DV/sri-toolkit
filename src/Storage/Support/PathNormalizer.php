<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage\Support;

use MTZ\Toolkit\Storage\Exceptions\StorageException;

final class PathNormalizer
{
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, "\0") || str_contains($path, '../') || str_contains($path, '..\\') || $path === '..')
        {
            throw StorageException::invalidPath($path);
        }

        return preg_replace('#/+#', '/', $path) ?? $path;
    }

    public static function segment(string|int $segment): string
    {
        $segment = trim((string) $segment);

        if ($segment === '')
        {
            throw StorageException::invalidPath($segment);
        }

        $segment = preg_replace('/[^A-Za-z0-9_\-.]/', '-', $segment) ?? $segment;

        return trim($segment, '-');
    }
}
