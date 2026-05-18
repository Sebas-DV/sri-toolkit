<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMDocument;
use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;
use MTZ\Toolkit\XMLMaker\Support\DomBuilder;

abstract class AbstractXmlDocumentBuilder
{
    protected DOMDocument $document;
    protected DomBuilder $dom;

    public function __construct(
        protected readonly XmlMakerConfig $config = new XmlMakerConfig(),
    ) {
    }

    protected function createDocument(XmlGenerationData $data): DOMDocument
    {
        $this->document = new DOMDocument(
            version: $this->config->xmlVersion,
            encoding: $this->config->encoding,
        );

        $this->document->formatOutput = $this->config->formatOutput;
        $this->document->preserveWhiteSpace = false;
        $this->dom = new DomBuilder($this->document);

        return $this->document;
    }

    /**
     * @throws DOMException
     */
    protected function createRoot(XmlGenerationData $data): DOMElement
    {
        $root = $this->document->createElement($data->documentType->rootElement());
        $root->setAttribute('id', $this->config->documentId);
        $root->setAttribute('version', $data->documentType->version());

        $this->document->appendChild($root);

        return $root;
    }

    /**
     * @throws DOMException
     */
    protected function appendTaxInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);

        $company = new ArrayReader($reader->array('company'));
        $establishment = new ArrayReader($reader->array('establishment'));
        $emissionPoint = new ArrayReader($reader->array('emission_point'));

        $taxInformation = $this->dom->child($root, 'infoTributaria');

        $this->dom->append($taxInformation, 'ambiente', $data->environment->value);
        $this->dom->append($taxInformation, 'tipoEmision', '1');
        $this->dom->append($taxInformation, 'razonSocial', $company->string('legal_name'));
        $this->dom->append($taxInformation, 'nombreComercial', $company->nullableString('trade_name'));
        $this->dom->append($taxInformation, 'ruc', $company->string('ruc'));
        $this->dom->append($taxInformation, 'claveAcceso', $data->accessKey);
        $this->dom->append($taxInformation, 'codDoc', $data->documentType->sriCode());
        $this->dom->append($taxInformation, 'estab', $establishment->string('code'));
        $this->dom->append($taxInformation, 'ptoEmi', $emissionPoint->string('code'));
        $this->dom->append($taxInformation, 'secuencial', $reader->string('sequential'));
        $this->dom->append($taxInformation, 'dirMatriz', $company->string('head_office_address'));

        $this->dom->append($taxInformation, 'agenteRetencion', $company->nullableString('withholding_agent'));
        $this->dom->append($taxInformation, 'contribuyenteRimpe', $company->nullableString('rimpe_regime_taxpayer'));
    }

    /**
     * @throws DOMException
     */
    protected function appendAdditionalInformation(DOMElement $root, array $additionalInformation): void
    {
        if ($additionalInformation === [])
        {
            return;
        }

        $info = $this->dom->child($root, 'infoAdicional');

        foreach ($additionalInformation as $name => $value)
        {
            if ($value === null || $value === '')
            {
                continue;
            }

            $field = $this->document->createElement('campoAdicional');
            $field->setAttribute('nombre', (string) $name);
            $field->appendChild($this->document->createTextNode((string) $value));

            $info->appendChild($field);
        }
    }
}
