<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMDocument;
use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Contracts\XmlDocumentBuilderInterface;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;
use MTZ\Toolkit\XMLMaker\Support\DomBuilder;

abstract class AbstractXmlDocumentBuilder implements XmlDocumentBuilderInterface
{
    protected DOMDocument $document;
    protected DomBuilder $dom;

    public function __construct(
        protected readonly XmlMakerConfig $config = new XmlMakerConfig(),
    ) {
    }

    /**
     * @throws DOMException
     */
    final public function build(XmlGenerationData $data): GeneratedXml
    {
        $this->createDocument($data);

        $root = $this->createRoot($data);

        $this->appendTaxInformation($root, $data);
        $this->appendDocumentInformation($root, $data);
        $this->appendAdditionalInformation($root, $this->additionalInformation($data));

        return new GeneratedXml(
            documentType: $data->documentType,
            accessKey: $data->accessKey,
            document: $this->document,
        );
    }

    /**
     * @throws DOMException
     */
    abstract protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void;

    protected function additionalInformation(XmlGenerationData $data): array
    {
        return $data->data['additional_info'] ?? [];
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

    /**
     * @throws DOMException
     */
    protected function appendBuyerInformation(DOMElement $parent, ArrayReader $buyer): void
    {
        $this->dom->append($parent, 'tipoIdentificacionComprador', $buyer->string('identification_type'));
        $this->dom->append($parent, 'razonSocialComprador', $buyer->string('name'));
        $this->dom->append($parent, 'identificacionComprador', $buyer->string('identification_number'));
    }

    /**
     * @throws DOMException
     */
    protected function appendModifiedDocument(DOMElement $parent, ArrayReader $referencedDocument): void
    {
        $this->dom->append($parent, 'codDocModificado', $referencedDocument->string('document_type'));
        $this->dom->append($parent, 'numDocModificado', $referencedDocument->string('number'));
        $this->dom->append($parent, 'fechaEmisionDocSustento', $referencedDocument->string('emission_date'));
    }

    /**
     * @throws DOMException
     */
    protected function appendTaxTotals(
        DOMElement $parent,
        array $taxTotals,
        string $containerName = 'totalConImpuestos',
        string $itemName = 'totalImpuesto',
        bool $includeRate = false,
    ): void {
        if ($taxTotals === [])
        {
            throw InvalidXmlDataException::emptyItems('tax_totals');
        }

        $container = $this->dom->child($parent, $containerName);

        foreach ($taxTotals as $taxTotal)
        {
            $reader = new ArrayReader($taxTotal);

            $tax = $this->dom->child($container, $itemName);

            $this->dom->append($tax, 'codigo', $reader->string('code'));
            $this->dom->append($tax, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($tax, 'descuentoAdicional', $reader->nullableString('additional_discount'));
            $this->dom->append($tax, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($tax, 'tarifa', $includeRate ? $reader->nullableString('rate') : null);
            $this->dom->append($tax, 'valor', $reader->string('value'));
            $this->dom->append($tax, 'valorDevolucionIva', $reader->nullableString('refund_value'));
        }
    }

    /**
     * @throws DOMException
     */
    protected function appendPayments(
        DOMElement $parent,
        array $payments,
        bool $includeTerms = true,
        string $field = 'payments',
        bool $required = false,
    ): void {
        if ($payments === [])
        {
            if ($required)
            {
                throw InvalidXmlDataException::emptyItems($field);
            }

            return;
        }

        $paymentsElement = $this->dom->child($parent, 'pagos');

        foreach ($payments as $payment)
        {
            $reader = new ArrayReader($payment);

            $paymentElement = $this->dom->child($paymentsElement, 'pago');

            $this->dom->append($paymentElement, 'formaPago', $reader->string('method'));
            $this->dom->append($paymentElement, 'total', $reader->string('total'));
            $this->dom->append($paymentElement, 'plazo', $includeTerms ? $reader->nullableString('term') : null);
            $this->dom->append($paymentElement, 'unidadTiempo', $includeTerms ? $reader->nullableString('time_unit') : null);
        }
    }

    /**
     * @throws DOMException
     */
    protected function appendLineTaxes(DOMElement $parent, array $taxes, string $field = 'details.*.taxes'): void
    {
        if ($taxes === [])
        {
            throw InvalidXmlDataException::emptyItems($field);
        }

        $taxesElement = $this->dom->child($parent, 'impuestos');

        foreach ($taxes as $tax)
        {
            $reader = new ArrayReader($tax);

            $taxElement = $this->dom->child($taxesElement, 'impuesto');

            $this->dom->append($taxElement, 'codigo', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($taxElement, 'tarifa', $reader->string('rate'));
            $this->dom->append($taxElement, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'valor', $reader->string('value'));
            $this->dom->append($taxElement, 'valorDevolucionIva', $reader->nullableString('refund_value'));
        }
    }

    /**
     * @throws DOMException
     */
    protected function appendAdditionalDetailFields(DOMElement $parent, array $additionalInfo): void
    {
        if ($additionalInfo === [])
        {
            return;
        }

        $additionalDetails = $this->dom->child($parent, 'detallesAdicionales');

        foreach ($additionalInfo as $name => $value)
        {
            if ($value === null || $value === '')
            {
                continue;
            }

            $additionalDetail = $this->document->createElement('detAdicional');
            $additionalDetail->setAttribute('nombre', (string) $name);
            $additionalDetail->setAttribute('valor', (string) $value);

            $additionalDetails->appendChild($additionalDetail);
        }
    }
}
