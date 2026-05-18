<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Exceptions;

final class InvalidXmlException extends SignerException
{
    public static function empty(): self
    {
        return new self('The XML is empty.');
    }

    public static function cannotLoad(): self
    {
        return new self('The XML could not be loaded.');
    }

    public static function missingDocumentId(string $expectedId): self
    {
        return new self("The XML root element must contain id=\"$expectedId\".");
    }

    public static function rootContainsNamespace(): self
    {
        return new self('The XML root element should not contain extra namespaces before signing.');
    }
}
