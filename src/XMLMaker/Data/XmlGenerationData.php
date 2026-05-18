<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Data;

use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;

/**
 * Immutable value object carrying all data needed to generate an SRI XML document.
 *
 * Includes the document type, target environment, pre-computed access key,
 * and the raw payload array that builders extract values from.
 */
final readonly class XmlGenerationData
{
    /**
     * @param XmlDocumentType $documentType The type of SRI electronic document to generate.
     * @param XmlEnvironment $environment The SRI environment (testing or production).
     * @param string $accessKey The pre-computed SRI access key.
     * @param array $data The raw data payload used by document builders.
     */
    public function __construct(
        public XmlDocumentType $documentType,
        public XmlEnvironment $environment,
        public string $accessKey,
        public array $data,
    ) {
    }

    /**
     * Named constructor for fluent creation of XmlGenerationData instances.
     *
     * @param XmlDocumentType $documentType The type of SRI electronic document.
     * @param XmlEnvironment $environment The target SRI environment.
     * @param string $accessKey The pre-computed SRI access key.
     * @param array $data The raw data payload.
     * @return self A new XmlGenerationData instance.
     */
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
