<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

/**
 * Thrown when the provided SRI access key does not match the expected 49-digit format.
 */
final class InvalidAccessKeyException extends SenderException
{
    /**
     * Creates an exception for an invalid access key.
     *
     * @param string $accessKey The invalid access key that was provided.
     * @return self
     */
    public static function make(string $accessKey): self
    {
        return new self("The access key [$accessKey] is invalid.");
    }
}
