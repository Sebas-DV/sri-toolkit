<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Validation;

use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

/**
 * Validates generated SRI XML against the schema for a document type.
 */
interface SchemaValidatorInterface
{
    /**
     * Validates an XML string against the schema for the given document type.
     *
     * @param string $xml The XML document to validate.
     * @param XmlDocumentType $type The document type whose schema should be used.
     * @return list<string> The validation error messages; an empty list means valid.
     */
    public function validate(string $xml, XmlDocumentType $type): array;

    /**
     * Returns whether the XML validates against the schema for the given document type.
     *
     * @param string $xml The XML document to validate.
     * @param XmlDocumentType $type The document type whose schema should be used.
     * @return bool
     */
    public function isValid(string $xml, XmlDocumentType $type): bool;
}
