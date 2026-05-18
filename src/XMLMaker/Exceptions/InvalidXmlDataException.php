<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Exceptions;

/**
 * Thrown when required XML data fields are missing, invalid, or empty.
 */
final class InvalidXmlDataException extends XmlGeneratorException
{
    /**
     * Creates an exception for a missing required field.
     *
     * @param string $field The name of the missing field.
     * @return self A new exception instance.
     */
    public static function missingField(string $field): self
    {
        return new self("Missing field required: $field");
    }

    /**
     * Creates an exception for a field that is not an array when one was expected.
     *
     * @param string $field The name of the field with an invalid type.
     * @return self A new exception instance.
     */
    public static function invalidArray(string $field): self
    {
        return new self("The XML file [$field] must be an array.");
    }

    /**
     * Creates an exception for a required array field that is empty.
     *
     * @param string $field The name of the empty field.
     * @return self A new exception instance.
     */
    public static function emptyItems(string $field): self
    {
        return new self("The XML file [$field] must contain at least one item.");
    }
}
