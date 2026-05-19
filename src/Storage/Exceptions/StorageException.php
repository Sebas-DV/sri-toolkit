<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage\Exceptions;

use RuntimeException;
use Throwable;

class StorageException extends RuntimeException
{
    public static function invalidPath(string $path): self
    {
        return new self("Invalid path: $path");
    }

    public static function cannotWrite(string $path, ?Throwable $previous = null): self
    {
        return new self("Cannot write to path: $path", previous: $previous);
    }

    public static function cannotRead(string $path, ?Throwable $previous = null): self
    {
        return new self("Cannot read from path: $path", previous: $previous);
    }

    public static function cannotDelete(string $path, ?Throwable $previous = null): self
    {
        return new self("Cannot delete path: $path", previous: $previous);
    }
}
