<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Enums;

enum DocumentType: string
{
    case Invoice = '01';
    case PurchaseSettlement = '03';
    case CreditNote = '04';
    case DebitNote = '05';
    case RemissionGuide = '06';
    case RetentionVoucher = '07';
}
