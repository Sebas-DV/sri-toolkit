<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Builds SRI Debit Note (Nota de Débito) XML documents.
 */
final class DebitNoteXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * Appends debit-note-specific information to the root element.
     *
     * @param DOMElement $root The root element of the XML document.
     * @param XmlGenerationData $data The generation data including debit note payload.
     * @throws DOMException When a required DOM element cannot be created.
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $customer = new ArrayReader($reader->array('customer'));
        $referencedDocument = new ArrayReader($reader->array('referenced_document'));

        $debitNoteInformation = $this->dom->child($root, 'infoNotaDebito');

        $this->dom->append($debitNoteInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($debitNoteInformation, 'dirEstablecimiento', $reader->nullableString('establishment_address'));

        $this->appendBuyerInformation($debitNoteInformation, $customer);

        $this->dom->append(
            $debitNoteInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($debitNoteInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->appendModifiedDocument($debitNoteInformation, $referencedDocument);

        $this->dom->append($debitNoteInformation, 'totalSinImpuestos', $reader->string('total_without_taxes'));

        $this->appendDebitNoteTaxes($debitNoteInformation, $reader->array('tax_totals'));

        $this->dom->append($debitNoteInformation, 'valorTotal', $reader->string('total_amount'));

        $this->appendPayments($debitNoteInformation, $reader->nullableArray('payments'));
        $this->appendReasons($root, $data);
    }

    /**
     * Appends the debit note tax block (impuestos) to the info element.
     *
     * @param DOMElement $parent The info element to append taxes to.
     * @param array $taxTotals Array of tax entries with code, percentage_code, rate, taxable_base, and value.
     * @throws InvalidXmlDataException When tax_totals is empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendDebitNoteTaxes(DOMElement $parent, array $taxTotals): void
    {
        if ($taxTotals === [])
        {
            throw InvalidXmlDataException::emptyItems('tax_totals');
        }

        $taxesElement = $this->dom->child($parent, 'impuestos');

        foreach ($taxTotals as $taxTotal)
        {
            $reader = new ArrayReader($taxTotal);

            $taxElement = $this->dom->child($taxesElement, 'impuesto');

            $this->dom->append($taxElement, 'codigo', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($taxElement, 'tarifa', $reader->string('rate'));
            $this->dom->append($taxElement, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'valor', $reader->string('value'));
        }
    }

    /**
     * Appends the debit note reasons (motivos) section to the root element.
     *
     * @param DOMElement $root The root element to append reasons to.
     * @param XmlGenerationData $data The generation data containing reasons.
     * @throws InvalidXmlDataException When reasons are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendReasons(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $reasons = $reader->array('reasons');

        if ($reasons === [])
        {
            throw InvalidXmlDataException::emptyItems('reasons');
        }

        $reasonsElement = $this->dom->child($root, 'motivos');

        foreach ($reasons as $reason)
        {
            $reasonReader = new ArrayReader($reason);

            $reasonElement = $this->dom->child($reasonsElement, 'motivo');

            $this->dom->append($reasonElement, 'razon', $reasonReader->string('reason'));
            $this->dom->append($reasonElement, 'valor', $reasonReader->string('amount'));
        }
    }
}
