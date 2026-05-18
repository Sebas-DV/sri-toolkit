<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Exceptions;

final class UnsupportedDocumentTypeException extends XmlGeneratorException
{
    public static function make(string $type): self
    {
        return new self("Unsupported XML Document type: $type");
    }
}
