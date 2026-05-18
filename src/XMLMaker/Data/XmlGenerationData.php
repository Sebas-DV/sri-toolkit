<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Data;

use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;

final readonly class XmlGenerationData
{
    public function __construct(
        public XmlDocumentType $documentType,
        public XmlEnvironment $environment,
        public string $accessKey,
        public array $data,
    ) {
    }

    public static function make(
        XmlDocumentType $documentType,
        XmlEnvironment $environment,
        string $accessKey,
        array $data,
    ): self {
        return new self(
            documentType: $documentType,
            environment: $environment,
            accessKey: $accessKey,
            data: $data,
        );
    }
}
