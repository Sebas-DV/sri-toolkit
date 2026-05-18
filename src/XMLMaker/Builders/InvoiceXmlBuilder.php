<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Builds SRI Invoice (Factura) XML documents.
 */
final class InvoiceXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * Appends invoice-specific information to the root element.
     *
     * @param DOMElement $root The root element of the XML document.
     * @param XmlGenerationData $data The generation data including invoice payload.
     * @throws DOMException When a required DOM element cannot be created.
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $customer = new ArrayReader($reader->array('customer'));

        $invoiceInformation = $this->dom->child($root, 'infoFactura');

        $this->dom->append($invoiceInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($invoiceInformation, 'dirEstablecimiento', $reader->string('establishment_address'));
        $this->dom->append(
            $invoiceInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($invoiceInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->appendBuyerInformation($invoiceInformation, $customer);
        $this->dom->append($invoiceInformation, 'guiaRemision', $reader->nullableString('delivery_guide'));
        $this->dom->append($invoiceInformation, 'direccionComprador', $customer->nullableString('address'));

        $this->dom->append($invoiceInformation, 'totalSinImpuestos', $reader->string('total_without_taxes'));
        $this->dom->append($invoiceInformation, 'totalSubsidio', $reader->nullableString('total_subsidy'));
        $this->dom->append($invoiceInformation, 'totalDescuento', $reader->string('total_discount'));
        $this->appendReimbursementTotals($invoiceInformation, $reader->nullableArray('reimbursement'));

        $this->appendTaxTotals($invoiceInformation, $reader->array('tax_totals'));

        $this->dom->append($invoiceInformation, 'propina', $reader->nullableString('tip'));
        $this->dom->append($invoiceInformation, 'importeTotal', $reader->string('total_amount'));
        $this->dom->append($invoiceInformation, 'moneda', $reader->nullableString('currency') ?? 'DOLAR');

        $this->appendPayments($invoiceInformation, $reader->nullableArray('payments'));
        $this->dom->append($invoiceInformation, 'valorRetIva', $reader->nullableString('total_iva_amount'));
        $this->dom->append($invoiceInformation, 'valorRetRenta', $reader->nullableString('total_renta_amount'));
        $this->appendDetails($root, $data);
    }

    /**
     * Appends reimbursement totals to the invoice information section.
     *
     * @param DOMElement $invoiceInformation The invoice info element to append to.
     * @param array $reimbursement Array with document_code, total, taxable_base_total, and tax_total fields.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReimbursementTotals(DOMElement $invoiceInformation, array $reimbursement): void
    {
        if ($reimbursement === [])
        {
            return;
        }

        $reader = new ArrayReader($reimbursement);

        $this->dom->append($invoiceInformation, 'codDocReembolso', $reader->string('document_code'));
        $this->dom->append($invoiceInformation, 'totalComprobantesReembolso', $reader->string('total'));
        $this->dom->append($invoiceInformation, 'totalBaseImponibleReembolso', $reader->string('taxable_base_total'));
        $this->dom->append($invoiceInformation, 'totalImpuestoReembolso', $reader->string('tax_total'));
    }

    /**
     * Appends the invoice detail lines (detalles) to the root element.
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
            $detailsReader = new ArrayReader($detail);

            $detail = $this->dom->child($detailsElement, 'detalle');

            $this->dom->append($detail, 'codigoPrincipal', $detailsReader->string('main_code'));
            $this->dom->append($detail, 'codigoAuxiliar', $detailsReader->nullableString('auxiliary_code'));
            $this->dom->append($detail, 'descripcion', $detailsReader->string('description'));
            $this->dom->append($detail, 'unidadMedida', $detailsReader->nullableString('unit'));
            $this->dom->append($detail, 'cantidad', $detailsReader->string('quantity'));
            $this->dom->append($detail, 'precioUnitario', $detailsReader->string('unit_price'));
            $this->dom->append($detail, 'precioSinSubsidio', $detailsReader->nullableString('unit_price_without_subsidy'));
            $this->dom->append($detail, 'descuento', $detailsReader->nullableString('discount') ?? '0.00');
            $this->dom->append($detail, 'precioTotalSinImpuesto', $detailsReader->string('total_without_tax'));

            $this->appendAdditionalDetailFields($detail, $detailsReader->nullableArray('additional_info'));
            $this->appendLineTaxes($detail, $detailsReader->array('taxes'));
        }
    }
}
