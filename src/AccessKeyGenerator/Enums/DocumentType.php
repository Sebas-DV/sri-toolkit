<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Enums;

/**
 * SRI electronic document types and their corresponding two-digit codes.
 */
enum DocumentType: string
{
    case Invoice = '01';
    case PurchaseSettlement = '03';
    case CreditNote = '04';
    case DebitNote = '05';
    case RemissionGuide = '06';
    case RetentionVoucher = '07';
}
