<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Data;

/**
 * Value object representing the result of a signing operation.
 *
 * Contains the signed XML string along with metadata about the signature.
 */
final readonly class SignedXmlResult
{
    /**
     * @param string $xml The signed XML document as a string.
     * @param string $signatureId The unique identifier of the generated signature.
     * @param string $signedAt The timestamp when the document was signed.
     */
    public function __construct(
        public string $xml,
        public string $signatureId,
        public string $signedAt,
    ) {
    }

    /**
     * Return the signed XML as a string.
     *
     * @return string The signed XML content.
     */
    public function toString(): string
    {
        return $this->xml;
    }
}
