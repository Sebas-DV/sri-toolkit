<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Contracts;

use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;

interface XmlDocumentBuilderInterface
{
    public function build(XmlGenerationData $data): GeneratedXml;
}
