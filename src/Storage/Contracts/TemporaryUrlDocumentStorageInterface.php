<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Storage\Contracts;

use DateTimeInterface;

interface TemporaryUrlDocumentStorageInterface extends DocumentStorageInterface
{
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt): string;
}
