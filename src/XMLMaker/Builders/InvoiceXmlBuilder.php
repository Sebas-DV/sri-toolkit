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
        $export = new ArrayReader($reader->nullableArray('export'));

        $info = $this->dom->child($root, 'infoFactura');

        $this->dom->append($info, 'fechaEmision', $reader->string('date'));
        $this->dom->append($info, 'dirEstablecimiento', $reader->string('establishment_address'));
        $this->dom->append(
            $info,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($info, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->appendExportHeader($info, $export);

        $this->appendBuyerInformation($info, $customer, $reader->nullableString('delivery_guide'));
        $this->dom->append($info, 'direccionComprador', $customer->nullableString('address'));

        $this->dom->append($info, 'totalSinImpuestos', $reader->string('total_without_taxes'));
        $this->dom->append($info, 'totalSubsidio', $reader->nullableString('total_subsidy'));
        $this->dom->append($info, 'incoTermTotalSinImpuestos', $export->nullableString('incoterm_subtotal'));
        $this->dom->append($info, 'totalDescuento', $reader->string('total_discount'));
        $this->appendReimbursementTotals($info, $reader->nullableArray('reimbursement'));

        $this->appendTaxTotals($info, $reader->array('tax_totals'));

        $this->dom->append($info, 'propina', $reader->nullableString('tip'));
        $this->appendInternationalCosts($info, $export);
        $this->dom->append($info, 'importeTotal', $reader->string('total_amount'));
        $this->dom->append($info, 'moneda', $reader->nullableString('currency') ?? 'DOLAR');
        $this->dom->append($info, 'placa', $reader->nullableString('plate'));

        $this->appendPayments($info, $reader->nullableArray('payments'));
        $this->dom->append($info, 'valorRetIva', $reader->nullableString('total_iva_amount'));
        $this->dom->append($info, 'valorRetRenta', $reader->nullableString('total_renta_amount'));

        $this->appendDetails($root, $data);

        $this->appendReimbursements($root, $reader->nullableArray('reimbursements'));
        $this->appendSubstituteDeliveryGuide($root, $reader->nullableArray('substitute_delivery_guide'));
        $this->appendThirdPartyItems($root, $reader->nullableArray('third_party_items'));
        $this->appendFiscalMachine($root, $reader->nullableArray('fiscal_machine'));
    }

    /**
     * Appends the export header fields (comercioExterior … paisAdquisicion) to infoFactura.
     *
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendExportHeader(DOMElement $info, ArrayReader $export): void
    {
        $this->dom->append($info, 'comercioExterior', $export->nullableString('foreign_trade'));
        $this->dom->append($info, 'incoTermFactura', $export->nullableString('incoterm'));
        $this->dom->append($info, 'lugarIncoTerm', $export->nullableString('incoterm_place'));
        $this->dom->append($info, 'paisOrigen', $export->nullableString('origin_country'));
        $this->dom->append($info, 'puertoEmbarque', $export->nullableString('shipment_port'));
        $this->dom->append($info, 'puertoDestino', $export->nullableString('destination_port'));
        $this->dom->append($info, 'paisDestino', $export->nullableString('destination_country'));
        $this->dom->append($info, 'paisAdquisicion', $export->nullableString('acquisition_country'));
    }

    /**
     * Appends the international cost fields to infoFactura (export invoices).
     *
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendInternationalCosts(DOMElement $info, ArrayReader $export): void
    {
        $this->dom->append($info, 'fleteInternacional', $export->nullableString('international_freight'));
        $this->dom->append($info, 'seguroInternacional', $export->nullableString('international_insurance'));
        $this->dom->append($info, 'gastosAduaneros', $export->nullableString('customs_expenses'));
        $this->dom->append($info, 'gastosTransporteOtros', $export->nullableString('other_transport_expenses'));
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

    /**
     * Appends the reimbursements block (reembolsos) for reimbursement invoices.
     *
     * @param array $reimbursements List of reimbursement detail entries.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReimbursements(DOMElement $root, array $reimbursements): void
    {
        if ($reimbursements === [])
        {
            return;
        }

        $container = $this->dom->child($root, 'reembolsos');

        foreach ($reimbursements as $reimbursement)
        {
            $reader = new ArrayReader($reimbursement);

            $detail = $this->dom->child($container, 'reembolsoDetalle');

            $this->dom->append($detail, 'tipoIdentificacionProveedorReembolso', $reader->string('provider_identification_type'));
            $this->dom->append($detail, 'identificacionProveedorReembolso', $reader->string('provider_identification_number'));
            $this->dom->append($detail, 'codPaisPagoProveedorReembolso', $reader->nullableString('provider_payment_country'));
            $this->dom->append($detail, 'tipoProveedorReembolso', $reader->string('provider_type'));
            $this->dom->append($detail, 'codDocReembolso', $reader->string('document_code'));
            $this->dom->append($detail, 'estabDocReembolso', $reader->string('establishment_code'));
            $this->dom->append($detail, 'ptoEmiDocReembolso', $reader->string('emission_point_code'));
            $this->dom->append($detail, 'secuencialDocReembolso', $reader->string('sequential'));
            $this->dom->append($detail, 'fechaEmisionDocReembolso', $reader->string('emission_date'));
            $this->dom->append($detail, 'numeroautorizacionDocReemb', $reader->string('authorization_number'));

            $this->appendReimbursementTaxes($detail, $reader->array('taxes'));
        }
    }

    /**
     * Appends the reimbursement tax details (detalleImpuestos) to a reimbursement detail.
     *
     * @param array $taxes List of reimbursement tax entries.
     * @throws InvalidXmlDataException When taxes are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReimbursementTaxes(DOMElement $detail, array $taxes): void
    {
        if ($taxes === [])
        {
            throw InvalidXmlDataException::emptyItems('reimbursements.*.taxes');
        }

        $container = $this->dom->child($detail, 'detalleImpuestos');

        foreach ($taxes as $tax)
        {
            $reader = new ArrayReader($tax);

            $taxElement = $this->dom->child($container, 'detalleImpuesto');

            $this->dom->append($taxElement, 'codigo', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));

            $this->dom->append($taxElement, 'tarifa', (string) (int) round((float) $reader->string('rate')));
            $this->dom->append($taxElement, 'baseImponibleReembolso', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'impuestoReembolso', $reader->string('value'));
        }
    }

    /**
     * Appends the delivery-guide
     *
     * @param array $substitute The substitute delivery guide data.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendSubstituteDeliveryGuide(DOMElement $root, array $substitute): void
    {
        if ($substitute === [])
        {
            return;
        }

        $reader = new ArrayReader($substitute);

        $element = $this->dom->child($root, 'infoSustitutivaGuiaRemision');

        $this->dom->append($element, 'dirPartida', $reader->string('start_address'));
        $this->dom->append($element, 'dirDestinatario', $reader->string('destination_address'));
        $this->dom->append($element, 'fechaIniTransporte', $reader->string('transport_start_date'));
        $this->dom->append($element, 'fechaFinTransporte', $reader->string('transport_end_date'));
        $this->dom->append($element, 'razonSocialTransportista', $reader->string('carrier_name'));
        $this->dom->append($element, 'tipoIdentificacionTransportista', $reader->string('carrier_identification_type'));
        $this->dom->append($element, 'rucTransportista', $reader->string('carrier_ruc'));
        $this->dom->append($element, 'placa', $reader->string('plate'));

        $destinations = $this->dom->child($element, 'destinos');

        foreach ($reader->array('destinations') as $destination)
        {
            $destinationReader = new ArrayReader($destination);

            $destinationElement = $this->dom->child($destinations, 'destino');

            $this->dom->append($destinationElement, 'motivoTraslado', $destinationReader->string('reason'));
            $this->dom->append($destinationElement, 'docAduaneroUnico', $destinationReader->nullableString('customs_document'));
            $this->dom->append($destinationElement, 'codEstabDestino', $destinationReader->nullableString('destination_establishment'));
            $this->dom->append($destinationElement, 'ruta', $destinationReader->nullableString('route'));
        }
    }

    /**
     * Appends the third-party charges block (otrosRubrosTerceros).
     *
     * @param array $items List of third-party charge entries.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendThirdPartyItems(DOMElement $root, array $items): void
    {
        if ($items === [])
        {
            return;
        }

        $container = $this->dom->child($root, 'otrosRubrosTerceros');

        foreach ($items as $item)
        {
            $reader = new ArrayReader($item);

            $rubro = $this->dom->child($container, 'rubro');

            $this->dom->append($rubro, 'concepto', $reader->string('concept'));
            $this->dom->append($rubro, 'total', $reader->string('total'));
        }
    }

    /**
     * Appends the fiscal machine block (maquinaFiscal).
     *
     * @param array $machine The fiscal machine data.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendFiscalMachine(DOMElement $root, array $machine): void
    {
        if ($machine === [])
        {
            return;
        }

        $reader = new ArrayReader($machine);

        $element = $this->dom->child($root, 'maquinaFiscal');

        $this->dom->append($element, 'marca', $reader->string('brand'));
        $this->dom->append($element, 'modelo', $reader->string('model'));
        $this->dom->append($element, 'serie', $reader->string('serial'));
    }
}
