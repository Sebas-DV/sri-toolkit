<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Data;

use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;

final readonly class RideData
{
    public function __construct(
        public RideDocumentType $documentType,
        public string $accessKey,
        public array $data,
        public ?string $authorizationNumber = null,
        public ?string $authorizationDate = null,
    ) {
    }

    public static function make(
        RideDocumentType $documentType,
        string $accessKey,
        array $data,
        ?string $authorizationNumber = null,
        ?string $authorizationDate = null,
    ): self {
        return new self(
            documentType: $documentType,
            accessKey: $accessKey,
            data: $data,
            authorizationNumber: $authorizationNumber,
            authorizationDate: $authorizationDate,
        );
    }
}
