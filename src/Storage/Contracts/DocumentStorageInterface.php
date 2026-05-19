<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage\Contracts;

interface DocumentStorageInterface
{
    public function put(string $path, string $content): void;
    public function get(string $path): string;
    public function exists(string $path): bool;
    public function delete(string $path): void;
}
