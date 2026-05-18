<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Exceptions;

final class InvalidXmlDataException extends XmlGeneratorException
{
    public static function missingField(string $field): self
    {
        return new self("Missing field required: $field");
    }

    public static function invalidArray(string $field): self
    {
        return new self("The XML file [$field] must be an array.");
    }

    public static function emptyItems(string $field): self
    {
        return new self("The XML file [$field] must contain at least one item.");
    }
}
