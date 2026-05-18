<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

final class DebitNoteXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * @throws DOMException
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

        $this->appendTaxTotals(
            parent: $debitNoteInformation,
            taxTotals: $reader->array('tax_totals'),
            containerName: 'impuestos',
            itemName: 'impuesto',
            includeRate: true,
        );

        $this->dom->append($debitNoteInformation, 'valorTotal', $reader->string('total_amount'));

        $this->appendPayments($debitNoteInformation, $reader->nullableArray('payments'));
        $this->appendReasons($root, $data);
    }

    /**
     * @throws DOMException
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
