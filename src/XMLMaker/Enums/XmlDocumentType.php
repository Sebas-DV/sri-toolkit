<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Enums;

enum XmlDocumentType: string
{
    case Invoice = 'invoice';
    case PurchaseSettlement = 'purchase-settlement';
    case CreditNote = 'credit-note';
    case DebitNote = 'debit-note';
    case DeliveryGuide = 'delivery-guide';
    case WithholdingReceipt = 'withholding-receipt';

    /**
     * Returns the two-digit document code for this document type.
     *
     * @return string The document code (e.g. '01' for invoices).
     */
    public function sriCode(): string
    {
        return match ($this)
        {
            self::Invoice => '01',
            self::PurchaseSettlement => '03',
            self::CreditNote => '04',
            self::DebitNote => '05',
            self::DeliveryGuide => '06',
            self::WithholdingReceipt => '07',
        };
    }

    /**
     * Returns the XML root element name for this document type.
     *
     * @return string The root element tag name (e.g. 'factura' for invoices).
     */
    public function rootElement(): string
    {
        return match ($this)
        {
            self::Invoice => 'factura',
            self::PurchaseSettlement => 'liquidacionCompra',
            self::CreditNote => 'notaCredito',
            self::DebitNote => 'notaDebito',
            self::DeliveryGuide => 'guiaRemision',
            self::WithholdingReceipt => 'comprobanteRetencion',
        };
    }

    /**
     * Returns the specification version for this document type.
     *
     * @return string The version string (e.g. '2.1.0' for invoices).
     */
    public function version(): string
    {
        return match ($this)
        {
            self::Invoice => '2.1.0',
            self::PurchaseSettlement => '1.1.0',
            self::CreditNote => '1.1.0',
            self::DebitNote => '1.0.0',
            self::DeliveryGuide => '1.1.0',
            self::WithholdingReceipt => '2.0.0',
        };
    }
}
