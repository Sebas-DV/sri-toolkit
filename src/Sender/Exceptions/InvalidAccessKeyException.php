<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

final class InvalidAccessKeyException extends SenderException
{
    public static function  make(string $accessKey): self
    {
        return new self("The access key [$accessKey] is invalid.");
    }
}