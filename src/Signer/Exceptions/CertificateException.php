<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Exceptions;

final class CertificateException extends SignerException
{
    public static function unreadable(string $path): self
    {
        return new self("The certificate file [$path] is unreadable.");
    }

    public static function invalidPassword(): self
    {
        return new self('The certificate password is invalid.');
    }

    public static function privateKeyNotFound(): self
    {
        return new self('The private key file was not found.');
    }

    public static function certificateNotFound(): self
    {
        return new self('The certificate file was not found.');
    }

    public static function cannotParseCertificate(): self
    {
        return new self('The certificate file could not be parsed.');
    }
}
