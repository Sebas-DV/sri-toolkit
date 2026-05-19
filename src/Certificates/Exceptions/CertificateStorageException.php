<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Certificates\Exceptions;

use RuntimeException;

final class CertificateStorageException extends RuntimeException
{
    public static function notFound(string $ownerKey): self
    {
        return new self("Certificate not found for owner: $ownerKey");
    }

    public static function cannotCreateTemporaryFile(): self
    {
        return new self('Could not create a temporary file.');
    }
}
