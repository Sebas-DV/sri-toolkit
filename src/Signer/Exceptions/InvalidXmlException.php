<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Exceptions;

/**
 * Exception thrown when the provided XML is invalid or does not meet signing requirements.
 */
final class InvalidXmlException extends SignerException
{
    /**
     * The provided XML string is empty.
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self('The XML is empty.');
    }

    /**
     * The XML string could not be parsed into a valid DOM document.
     *
     * @return self
     */
    public static function cannotLoad(): self
    {
        return new self('The XML could not be loaded.');
    }

    /**
     * The root element is missing the required ID attribute.
     *
     * @param string $expectedId The expected ID attribute value.
     * @return self
     */
    public static function missingDocumentId(string $expectedId): self
    {
        return new self("The XML root element must contain id=\"$expectedId\".");
    }

    /**
     * The root element already contains namespace declarations which should not be present before signing.
     *
     * @return self
     */
    public static function rootContainsNamespace(): self
    {
        return new self('The XML root element should not contain extra namespaces before signing.');
    }
}
