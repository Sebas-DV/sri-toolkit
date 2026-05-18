<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Contracts;

use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;

/**
 * Contract for SRI XML document builders.
 *
 * Each implementation constructs a specific type of SRI electronic document.
 */
interface XmlDocumentBuilderInterface
{
    /**
     * Builds a complete SRI XML document from the provided generation data.
     *
     * @param XmlGenerationData $data The document type, environment, access key, and payload.
     * @return GeneratedXml The fully built XML document wrapper.
     */
    public function build(XmlGenerationData $data): GeneratedXml;
}
