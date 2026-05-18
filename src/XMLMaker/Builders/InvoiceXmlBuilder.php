<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Contracts\XmlDocumentBuilderInterface;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

final class InvoiceXmlBuilder extends AbstractXmlDocumentBuilder implements XmlDocumentBuilderInterface
{
    /**
     * @throws DOMException
     */
    public function build(XmlGenerationData $data): GeneratedXml
    {
        $this->createDocument($data);

        $root = $this->createRoot($data);

        $this->appendTaxInformation($root, $data);
        $this->appendInvoiceInformation($root, $data);
        $this->appendDetails($root, $data);
        $this->appendAdditionalInformation($root, $data->data['additional_info'] ?? []);

        return new GeneratedXml(
            documentType: $data->documentType,
            accessKey: $data->accessKey,
            document: $this->document,
        );
    }

    /**
     * @throws DOMException
     */
    private function appendInvoiceInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);

        $customer = new ArrayReader($reader->array('customer'));

        $invoiceInformation = $this->dom->child($root, 'infoFactura');

        $this->dom->append($invoiceInformation, 'fechaEmision', $reader->string('date'));
        $this->dom->append($invoiceInformation, 'dirEstablecimiento', $reader->string('establishment_address'));
        $this->dom->append($invoiceInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));

        $this->dom->append($invoiceInformation, 'tipoIdentificacionComprador', $customer->string('identification_type'));
        $this->dom->append($invoiceInformation, 'razonSocialComprador', $customer->string('name'));
        $this->dom->append($invoiceInformation, 'identificacionComprador', $customer->string('identification_number'));
        $this->dom->append($invoiceInformation, 'direccionComprador', $customer->nullableString('address'));

        $this->dom->append($invoiceInformation, 'totalSinImpuestos', $reader->string('total_without_taxes'));
        $this->dom->append($invoiceInformation, 'totalDescuento', $reader->string('total_discount'));

        $this->appendTotalWithTaxes($invoiceInformation, $reader->array('tax_totals'));

        $this->dom->append($invoiceInformation, 'propina', $reader->nullableString('tip'));
        $this->dom->append($invoiceInformation, 'importeTotal', $reader->string('total_amount'));
        $this->dom->append($invoiceInformation, 'moneda', $reader->nullableString('currency') ?? 'DOLAR');

        $payments = $reader->nullableArray('payments');

        if ($payments !== [])
        {
            $this->appendPayments($invoiceInformation, $payments);
        }
    }

    /**
     * @throws DOMException
     */
    private function appendTotalWithTaxes(DOMElement $invoiceInformation, array $taxTotals): void
    {
        if ($taxTotals === [])
        {
            throw InvalidXmlDataException::emptyItems('tax_totals');
        }

        $totalWithTaxes = $this->dom->child($invoiceInformation, 'totalConImpuestos');

        foreach ($taxTotals as $taxTotal)
        {
            $reader = new ArrayReader($taxTotal);

            $totalTax = $this->dom->child($totalWithTaxes, 'totalImpuesto');

            $this->dom->append($totalTax, 'codigo', $reader->string('code'));
            $this->dom->append($totalTax, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($totalTax, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($totalTax, 'valor', $reader->string('value'));
        }
    }

    /**
     * @throws DOMException
     */
    private function appendPayments(DOMElement $invoiceInformation, array $payments): void
    {
        $paymentsElement = $this->dom->child($invoiceInformation, 'pagos');

        foreach ($payments as $payment)
        {
            $reader = new ArrayReader($payment);

            $paymentElement = $this->dom->child($paymentsElement, 'pago');

            $this->dom->append($paymentElement, 'formaPago', $reader->string('method'));
            $this->dom->append($paymentElement, 'total', $reader->string('total'));
            $this->dom->append($paymentElement, 'plazo', $reader->nullableString('term'));
            $this->dom->append($paymentElement, 'unidadTiempo', $reader->nullableString('time_unit'));
        }
    }

    /**
     * @throws DOMException
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
            $this->dom->append($detail, 'cantidad', $detailsReader->string('quantity'));
            $this->dom->append($detail, 'precioUnitario', $detailsReader->string('unit_price'));
            $this->dom->append($detail, 'descuento', $detailsReader->nullableString('discount'));
            $this->dom->append($detail, 'precioTotalSinImpuesto', $detailsReader->string('total_without_tax'));

            $this->appendDetailTaxes($detail, $detailsReader->array('taxes'));
        }
    }

    /**
     * @throws DOMException
     */
    private function appendDetailTaxes(DOMElement $detail, array $taxes): void
    {
        if ($taxes === [])
        {
            throw InvalidXmlDataException::emptyItems('details.*.taxes');
        }

        $taxesElement = $this->dom->child($detail, 'impuestos');

        foreach ($taxes as $tax)
        {
            $reader = new ArrayReader($tax);

            $taxElement = $this->dom->child($taxesElement, 'impuesto');

            $this->dom->append($taxElement, 'codigo', $reader->string('code'));
            $this->dom->append($taxElement, 'codigoPorcentaje', $reader->string('percentage_code'));
            $this->dom->append($taxElement, 'tarifa', $reader->string('rate'));
            $this->dom->append($taxElement, 'baseImponible', $reader->string('taxable_base'));
            $this->dom->append($taxElement, 'valor', $reader->string('value'));
        }
    }
}
