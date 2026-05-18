<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

/**
 * Thrown when a SOAP connection error occurs during communication with the SRI.
 */
final class ConnectionException extends SenderException
{
    /**
     * Creates a connection exception from a SOAP error message.
     *
     * @param string $message The underlying SOAP error message.
     * @return self
     */
    public static function soap(string $message): self
    {
        return new self("I could not connect to the SOAP server. $message");
    }
}
