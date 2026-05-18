<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Data;

final readonly class SignedXmlResult
{
    public function __construct(
        public string $xml,
        public string $signatureId,
        public string $signedAt,
    ) {
    }

    public function toString(): string
    {
        return $this->xml;
    }
}
