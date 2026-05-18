<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

final class CreditNoteXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * @throws DOMException
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $customer = new ArrayReader($reader->array('customer'));
        $referenceDocument = new ArrayReader($reader->array('referenced_document'));

        $creditNoteInformation = $this->dom->child($root, 'infoNotaCredito');

        $this->dom->append($creditNoteInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($creditNoteInformation, 'dirEstablecimiento', $reader->nullableString('establishment_address'));
        $this->appendBuyerInformation($creditNoteInformation, $customer);

        $this->dom->append(
            $creditNoteInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($creditNoteInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->appendModifiedDocument($creditNoteInformation, $referenceDocument);

        $this->dom->append($creditNoteInformation, 'totalSinImpuestos', $reader->string('total_without_taxes'));
        $this->dom->append($creditNoteInformation, 'valorModificacion', $reader->string('modified_document_total'));
        $this->dom->append($creditNoteInformation, 'moneda', $reader->nullableString('currency') ?? 'DOLAR');

        $this->appendTaxTotals($creditNoteInformation, $reader->array('tax_totals'));

        $this->dom->append($creditNoteInformation, 'motivo', $referenceDocument->string('reason'));
        $this->appendDetails($root, $data);
    }

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

            $this->dom->append($detailElement, 'codigoInterno', $detailReader->string('main_code'));
            $this->dom->append($detailElement, 'codigoAdicional', $detailReader->nullableString('auxiliary_code'));
            $this->dom->append($detailElement, 'descripcion', $detailReader->string('description'));
            $this->dom->append($detailElement, 'cantidad', $detailReader->string('quantity'));
            $this->dom->append($detailElement, 'precioUnitario', $detailReader->string('unit_price'));
            $this->dom->append($detailElement, 'descuento', $detailReader->nullableString('discount') ?? '0.00');
            $this->dom->append($detailElement, 'precioTotalSinImpuesto', $detailReader->string('total_without_tax'));

            $this->appendAdditionalDetailFields($detailElement, $detailReader->nullableArray('additional_info'));
            $this->appendLineTaxes($detailElement, $detailReader->array('taxes'));
        }
    }
}
