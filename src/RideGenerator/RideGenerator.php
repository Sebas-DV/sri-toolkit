<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator;

use MTZ\Toolkit\RideGenerator\Data\GeneratedRidePdf;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\Renders\MpdfRideRenderer;
use MTZ\Toolkit\RideGenerator\Templates\CreditNoteRideTemplate;
use MTZ\Toolkit\RideGenerator\Templates\DebitNoteRideTemplate;
use MTZ\Toolkit\RideGenerator\Templates\DeliveryGuideRideTemplate;
use MTZ\Toolkit\RideGenerator\Templates\InvoiceRideTemplate;
use MTZ\Toolkit\RideGenerator\Templates\PurchaseSettlementRideTemplate;
use MTZ\Toolkit\RideGenerator\Templates\WithholdingReceiptRideTemplate;

final readonly class RideGenerator
{
    public function __construct(
        private MpdfRideRenderer $renderer = new MpdfRideRenderer(),
    ) {
    }

    public function generate(RideData $data): GeneratedRidePdf
    {
        $html = match ($data->documentType)
        {
            RideDocumentType::Invoice => (new InvoiceRideTemplate())->render($data),
            RideDocumentType::CreditNote => (new CreditNoteRideTemplate())->render($data),
            RideDocumentType::DebitNote => (new DebitNoteRideTemplate())->render($data),
            RideDocumentType::WithholdingReceipt => (new WithholdingReceiptRideTemplate())->render($data),
            RideDocumentType::DeliveryGuide => (new DeliveryGuideRideTemplate())->render($data),
            RideDocumentType::PurchaseSettlement => (new PurchaseSettlementRideTemplate())->render($data),
        };

        return $this->renderer->render(
            html: $html,
            fileName: $this->filename($data),
        );
    }

    private function filename(RideData $data): string
    {
        return 'RIDE-' . strtoupper($data->documentType->value) . '-' . $data->accessKey . '.pdf';
    }
}
