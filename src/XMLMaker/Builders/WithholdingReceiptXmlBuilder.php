<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Builds SRI Withholding Receipt (Comprobante de Retención) XML documents.
 */
final class WithholdingReceiptXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * Appends withholding-receipt-specific information to the root element.
     *
     * @param DOMElement $root The root element of the XML document.
     * @param XmlGenerationData $data The generation data including withholding receipt payload.
     * @throws DOMException When a required DOM element cannot be created.
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $subject = new ArrayReader($reader->array('subject'));

        $withholdingInformation = $this->dom->child($root, 'infoCompRetencion');

        $this->dom->append($withholdingInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($withholdingInformation, 'dirEstablecimiento', $reader->nullableString('establishment_address'));
        $this->dom->append(
            $withholdingInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($withholdingInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->dom->append($withholdingInformation, 'tipoIdentificacionSujetoRetenido', $subject->string('identification_type'));
        $this->dom->append($withholdingInformation, 'tipoSujetoRetenido', $subject->nullableString('subject_type'));
        $this->dom->append($withholdingInformation, 'parteRel', $subject->string('related_party'));
        $this->dom->append($withholdingInformation, 'razonSocialSujetoRetenido', $subject->string('name'));
        $this->dom->append($withholdingInformation, 'identificacionSujetoRetenido', $subject->string('identification_number'));

        $this->dom->append($withholdingInformation, 'periodoFiscal', $reader->string('fiscal_period'));

        $this->appendSupportingDocuments($root, $data);
    }

    /**
     * Appends supporting documents (docsSustento) section to the root element.
     *
     * @param DOMElement $root The root element to append supporting documents to.
     * @param XmlGenerationData $data The generation data containing supporting documents.
     * @throws InvalidXmlDataException When supporting documents are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendSupportingDocuments(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $supportingDocuments = $reader->array('supporting_documents');

        if ($supportingDocuments === [])
        {
            throw InvalidXmlDataException::emptyItems('supporting_documents');
        }

        $supportingDocumentsElement = $this->dom->child($root, 'docsSustento');

        foreach ($supportingDocuments as $supportingDocument)
        {
            $supportingDocumentReader = new ArrayReader($supportingDocument);

            $supportingDocumentElement = $this->dom->child($supportingDocumentsElement, 'docSustento');

            $this->dom->append($supportingDocumentElement, 'codSustento', $supportingDocumentReader->string('support_code'));
            $this->dom->append($supportingDocumentElement, 'codDocSustento', $supportingDocumentReader->string('document_code'));
            $this->dom->append($supportingDocumentElement, 'numDocSustento', $supportingDocumentReader->string('document_number'));
            $this->dom->append($supportingDocumentElement, 'fechaEmisionDocSustento', $supportingDocumentReader->string('emission_date'));
            $this->dom->append($supportingDocumentElement, 'fechaRegistroContable', $supportingDocumentReader->nullableString('accounting_record_date'));
            $this->dom->append($supportingDocumentElement, 'numAutDocSustento', $supportingDocumentReader->nullableString('authorization_number'));
            $this->dom->append($supportingDocumentElement, 'pagoLocExt', $supportingDocumentReader->string('local_or_foreign_payment'));
            $this->dom->append($supportingDocumentElement, 'tipoRegi', $supportingDocumentReader->nullableString('tax_regime_type'));
            $this->dom->append($supportingDocumentElement, 'paisEfecPago', $supportingDocumentReader->nullableString('payment_country'));
            $this->dom->append($supportingDocumentElement, 'aplicConvDobTrib', $supportingDocumentReader->nullableString('double_taxation_agreement'));
            $this->dom->append($supportingDocumentElement, 'pagExtSujRetNorLeg', $supportingDocumentReader->nullableString('foreign_payment_subject_to_withholding'));
            $this->dom->append($supportingDocumentElement, 'pagoRegFis', $supportingDocumentReader->nullableString('tax_haven_payment'));

            $this->dom->append($supportingDocumentElement, 'totalComprobantesReembolso', $supportingDocumentReader->nullableString('refund_documents_total'));
            $this->dom->append($supportingDocumentElement, 'totalBaseImponibleReembolso', $supportingDocumentReader->nullableString('refund_taxable_base_total'));
            $this->dom->append($supportingDocumentElement, 'totalImpuestoReembolso', $supportingDocumentReader->nullableString('refund_tax_total'));

            $this->dom->append($supportingDocumentElement, 'totalSinImpuestos', $supportingDocumentReader->string('total_without_taxes'));
            $this->dom->append($supportingDocumentElement, 'importeTotal', $supportingDocumentReader->string('total_amount'));

            $this->appendSupportingDocumentTaxes($supportingDocumentElement, $supportingDocumentReader->array('taxes'));
            $this->appendWithholdings($supportingDocumentElement, $supportingDocumentReader->array('withholdings'));
            $this->appendReimbursements($supportingDocumentElement, $supportingDocumentReader->nullableArray('reimbursements'));

            $this->appendPayments(
                parent: $supportingDocumentElement,
                payments: $supportingDocumentReader->array('payments'),
                includeTerms: false,
                field: 'supporting_documents.*.payments',
                required: true,
            );
        }
    }

    /**
     * Appends supporting document taxes (impuestosDocSustento) to a supporting document element.
     *
     * @param DOMElement $supportingDocumentElement The supporting document element to append taxes to.
     * @param array $taxes Array of tax entries with code, percentage_code, taxable_base, rate, and value.
     * @throws InvalidXmlDataException When taxes are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendSupportingDocumentTaxes(DOMElement $supportingDocumentElement, array $taxes): void
    {
        if ($taxes === [])
        {
            throw InvalidXmlDataException::emptyItems('supporting_documents.*.taxes');
        }

        $taxesElement = $this->dom->child($supportingDocumentElement, 'impuestosDocSustento');

        foreach ($taxes as $tax)
        {
            $reader = new ArrayReader($tax);

            $taxElement = $this->dom->child($taxesElement, 'impuestoDocSustento');

            $this->dom->append($taxElement, 'codImpuestoDocSustento', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($taxElement, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'tarifa', $reader->string('rate'));
            $this->dom->append($taxElement, 'valorImpuesto', $reader->string('value'));
        }
    }

    /**
     * Appends reimbursements (reembolsos) to a supporting document element.
     *
     * @param DOMElement $supportingDocumentElement The supporting document element to append reimbursements to.
     * @param array $reimbursements Array of reimbursement entries.
     * @throws InvalidXmlDataException When reimbursement taxes are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReimbursements(DOMElement $supportingDocumentElement, array $reimbursements): void
    {
        if ($reimbursements === [])
        {
            return;
        }

        $reimbursementsElement = $this->dom->child($supportingDocumentElement, 'reembolsos');

        foreach ($reimbursements as $reimbursement)
        {
            $reader = new ArrayReader($reimbursement);

            $reimbursementElement = $this->dom->child($reimbursementsElement, 'reembolsoDetalle');

            $this->dom->append($reimbursementElement, 'tipoIdentificacionProveedorReembolso', $reader->string('provider_identification_type'));
            $this->dom->append($reimbursementElement, 'identificacionProveedorReembolso', $reader->string('provider_identification_number'));
            $this->dom->append($reimbursementElement, 'codPaisPagoProveedorReembolso', $reader->nullableString('provider_payment_country'));
            $this->dom->append($reimbursementElement, 'tipoProveedorReembolso', $reader->string('provider_type'));
            $this->dom->append($reimbursementElement, 'codDocReembolso', $reader->string('document_code'));
            $this->dom->append($reimbursementElement, 'estabDocReembolso', $reader->string('establishment_code'));
            $this->dom->append($reimbursementElement, 'ptoEmiDocReembolso', $reader->string('emission_point_code'));
            $this->dom->append($reimbursementElement, 'secuencialDocReembolso', $reader->string('sequential'));
            $this->dom->append($reimbursementElement, 'fechaEmisionDocReembolso', $reader->string('emission_date'));
            $this->dom->append($reimbursementElement, 'numeroAutorizacionDocReemb', $reader->string('authorization_number'));

            $this->appendReimbursementTaxes($reimbursementElement, $reader->array('taxes'));
        }
    }

    /**
     * Appends reimbursement tax details (detalleImpuestos) to a reimbursement element.
     *
     * @param DOMElement $reimbursementElement The reimbursement element to append taxes to.
     * @param array $taxes Array of tax entries with code, percentage_code, rate, taxable_base, and value.
     * @throws InvalidXmlDataException When taxes are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReimbursementTaxes(DOMElement $reimbursementElement, array $taxes): void
    {
        if ($taxes === [])
        {
            throw InvalidXmlDataException::emptyItems('supporting_documents.*.reimbursements.*.taxes');
        }

        $taxesElement = $this->dom->child($reimbursementElement, 'detalleImpuestos');

        foreach ($taxes as $tax)
        {
            $reader = new ArrayReader($tax);

            $taxElement = $this->dom->child($taxesElement, 'detalleImpuesto');

            $this->dom->append($taxElement, 'codigo', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($taxElement, 'tarifa', $reader->string('rate'));
            $this->dom->append($taxElement, 'baseImponibleReembolso', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'impuestoReembolso', $reader->string('value'));
        }
    }

    /**
     * Appends withholdings (retenciones) to a supporting document element.
     *
     * @param DOMElement $supportingDocumentElement The supporting document element to append withholdings to.
     * @param array $withholdings Array of withholding entries with code, withholding_code, taxable_base, percentage, and value.
     * @throws InvalidXmlDataException When withholdings are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendWithholdings(DOMElement $supportingDocumentElement, array $withholdings): void
    {
        if ($withholdings === [])
        {
            throw InvalidXmlDataException::emptyItems('supporting_documents.*.withholdings');
        }

        $withholdingsElement = $this->dom->child($supportingDocumentElement, 'retenciones');

        foreach ($withholdings as $withholding)
        {
            $reader = new ArrayReader($withholding);

            $withholdingElement = $this->dom->child($withholdingsElement, 'retencion');

            $this->dom->append($withholdingElement, 'codigo', $reader->string('code'));
            $this->dom->append($withholdingElement, 'codigoRetencion', $reader->string('withholding_code'));
            $this->dom->append($withholdingElement, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($withholdingElement, 'porcentajeRetener', $reader->string('percentage'));
            $this->dom->append($withholdingElement, 'valorRetenido', $reader->string('value'));
            $this->appendDividends($withholdingElement, $reader->nullableArray('dividends'));
            $this->appendBananaPurchase($withholdingElement, $reader->nullableArray('banana_purchase'));
        }
    }

    /**
     * Appends dividend information (dividendos) to a withholding element.
     *
     * @param DOMElement $withholdingElement The withholding element to append dividends to.
     * @param array $dividends Array with payment_date, corporate_income_tax, and fiscal_year_profit fields.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendDividends(DOMElement $withholdingElement, array $dividends): void
    {
        if ($dividends === [])
        {
            return;
        }

        $reader = new ArrayReader($dividends);
        $dividendsElement = $this->dom->child($withholdingElement, 'dividendos');

        $this->dom->append($dividendsElement, 'fechaPagoDiv', $reader->string('payment_date'));
        $this->dom->append($dividendsElement, 'imRentaSoc', $reader->string('corporate_income_tax'));
        $this->dom->append($dividendsElement, 'ejerFisUtDiv', $reader->string('fiscal_year_profit'));
    }

    /**
     * Appends banana box purchase information (compraCajBanano) to a withholding element.
     *
     * @param DOMElement $withholdingElement The withholding element to append banana purchase data to.
     * @param array $bananaPurchase Array with box_count and box_price fields.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendBananaPurchase(DOMElement $withholdingElement, array $bananaPurchase): void
    {
        if ($bananaPurchase === [])
        {
            return;
        }

        $reader = new ArrayReader($bananaPurchase);
        $bananaPurchaseElement = $this->dom->child($withholdingElement, 'compraCajBanano');

        $this->dom->append($bananaPurchaseElement, 'numCajBan', $reader->string('box_count'));
        $this->dom->append($bananaPurchaseElement, 'precCajBan', $reader->string('box_price'));
    }
}
