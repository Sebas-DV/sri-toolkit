<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Security\Exceptions;

use RuntimeException;
use Throwable;

final class EncryptionException extends RuntimeException
{
    public static function invalidKey(): self
    {
        return new self('The encryption key must be a valid 32-byte base64 encoded key.');
    }

    public static function encryptionFailed(Throwable $previous = null): self
    {
        return new self('Could not encrypt the given value.', previous: $previous);
    }

    public static function decryptionFailed(Throwable $previous = null): self
    {
        return new self('Could not decrypt the given value.', previous: $previous);
    }
}
