<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\XMLMaker;

use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Validation\SchemaValidatorInterface;

/**
 * Schema validator double returning a fixed set of errors.
 */
final readonly class StubSchemaValidator implements SchemaValidatorInterface
{
    /**
     * @param list<string> $errors The errors to return from validate().
     */
    public function __construct(
        private array $errors = [],
    ) {
    }

    public function validate(string $xml, XmlDocumentType $type): array
    {
        return $this->errors;
    }

    public function isValid(string $xml, XmlDocumentType $type): bool
    {
        return $this->errors === [];
    }
}
