<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Enums;

enum RideDocumentType: string
{
    case Invoice = 'invoice';
    case CreditNote = 'credit-note';
    case DebitNote = 'debit-note';
    case WithholdingReceipt = 'withholding-receipt';
    case DeliveryGuide = 'delivery-guide';
    case PurchaseSettlement = 'purchase-settlement';

    public function title(): string
    {
        return match ($this)
        {
            self::Invoice => 'FACTURA',
            self::CreditNote => 'NOTA DE CRÉDITO',
            self::DebitNote => 'NOTA DE DÉBITO',
            self::WithholdingReceipt => 'COMPROBANTE DE RETENCIÓN',
            self::DeliveryGuide => 'GUÍA DE REMISIÓN',
            self::PurchaseSettlement => 'LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS',
        };
    }
}
