<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Builds purchase settlement XML documents.
 */
final class PurchaseSettlementXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * Appends purchase settlement-specific information to the root element.
     *
     * @param DOMElement $root The root element of the XML document.
     * @param XmlGenerationData $data The generation data including purchase settlement payload.
     * @throws DOMException When a required DOM element cannot be created.
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $provider = new ArrayReader($reader->array('provider'));

        $settlementInformation = $this->dom->child($root, 'infoLiquidacionCompra');

        $this->dom->append($settlementInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($settlementInformation, 'dirEstablecimiento', $reader->string('establishment_address'));
        $this->dom->append(
            $settlementInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($settlementInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->appendProviderInformation($settlementInformation, $provider);

        $this->dom->append($settlementInformation, 'direccionProveedor', $provider->nullableString('address'));
        $this->dom->append($settlementInformation, 'totalSinImpuestos', $reader->string('total_without_taxes'));
        $this->dom->append($settlementInformation, 'totalDescuento', $reader->string('total_discount'));
        $this->appendTaxTotals($settlementInformation, $reader->array('tax_totals'));
        $this->dom->append($settlementInformation, 'importeTotal', $reader->string('total_amount'));
        $this->dom->append($settlementInformation, 'moneda', $reader->nullableString('currency') ?? 'DOLAR');
        $this->appendPayments($settlementInformation, $reader->nullableArray('payments'));
        $this->appendDetails($root, $data);
    }

    /**
     * Appends provider identification fields to a parent element.
     *
     * @param DOMElement $parent The parent element to append provider info to.
     * @param ArrayReader $provider Reader wrapping the provider data array.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendProviderInformation(DOMElement $parent, ArrayReader $provider): void
    {
        $this->dom->append($parent, 'tipoIdentificacionProveedor', $provider->string('identification_type'));
        $this->dom->append($parent, 'razonSocialProveedor', $provider->string('name'));
        $this->dom->append($parent, 'identificacionProveedor', $provider->string('identification_number'));
    }

    /**
     * Appends the purchase settlement detail lines to the root element.
     *
     * @param DOMElement $root The root element to append details to.
     * @param XmlGenerationData $data The generation data containing detail lines.
     * @throws InvalidXmlDataException When details are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendDetails(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $details = $reader->array('details');

        if ($details === [])
        {
            throw InvalidXmlDataException::emptyItems('details');
        }

        $detailsElement = $this->dom->child($root, 'detalles');

        foreach ($details as $detail)
        {
            $detailReader = new ArrayReader($detail);
            $detailElement = $this->dom->child($detailsElement, 'detalle');

            $this->dom->append($detailElement, 'codigoPrincipal', $detailReader->string('main_code'));
            $this->dom->append($detailElement, 'codigoAuxiliar', $detailReader->nullableString('auxiliary_code'));
            $this->dom->append($detailElement, 'descripcion', $detailReader->string('description'));
            $this->dom->append($detailElement, 'unidadMedida', $detailReader->nullableString('unit'));
            $this->dom->append($detailElement, 'cantidad', $detailReader->string('quantity'));
            $this->dom->append($detailElement, 'precioUnitario', $detailReader->string('unit_price'));
            $this->dom->append($detailElement, 'descuento', $detailReader->nullableString('discount') ?? '0.00');
            $this->dom->append($detailElement, 'precioTotalSinImpuesto', $detailReader->string('total_without_tax'));

            $this->appendAdditionalDetailFields($detailElement, $detailReader->nullableArray('additional_info'));
            $this->appendLineTaxes($detailElement, $detailReader->array('taxes'));
        }
    }
}
