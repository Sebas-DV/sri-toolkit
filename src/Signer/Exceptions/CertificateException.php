<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Exceptions;

/**
 * Exception thrown when a certificate file cannot be loaded, parsed, or validated.
 */
final class CertificateException extends SignerException
{
    /**
     * The certificate file is not readable.
     *
     * @param string $path The filesystem path that failed.
     * @return self
     */
    public static function unreadable(string $path): self
    {
        return new self("The certificate file [$path] is unreadable.");
    }

    /**
     * The password provided for the certificate is incorrect.
     *
     * @return self
     */
    public static function invalidPassword(): self
    {
        return new self('The certificate password is invalid.');
    }

    /**
     * The private key was not found in the certificate bundle.
     *
     * @return self
     */
    public static function privateKeyNotFound(): self
    {
        return new self('The private key file was not found.');
    }

    /**
     * The certificate was not found in the bundle.
     *
     * @return self
     */
    public static function certificateNotFound(): self
    {
        return new self('The certificate file was not found.');
    }

    /**
     * The certificate content could not be parsed.
     *
     * @return self
     */
    public static function cannotParseCertificate(): self
    {
        return new self('The certificate file could not be parsed.');
    }
}
