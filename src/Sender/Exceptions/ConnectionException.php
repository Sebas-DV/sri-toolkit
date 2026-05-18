<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

final class ConnectionException extends SenderException
{
    public static function soap(string $message): self
    {
        return new self("I could not connect to the SOAP server. $message");
    }
}
