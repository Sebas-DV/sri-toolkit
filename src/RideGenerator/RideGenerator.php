<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateRendererInterface;
use MTZ\Toolkit\RideGenerator\Data\GeneratedRidePdf;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Renders\DompdfRideRenderer;
use MTZ\Toolkit\RideGenerator\Renders\TwigRideTemplateRenderer;

final readonly class RideGenerator
{
    public function __construct(
        private RideTemplateRendererInterface $templateRenderer = new TwigRideTemplateRenderer(),
        private DompdfRideRenderer $renderer = new DompdfRideRenderer(),
    ) {
    }

    public function generate(RideData $data): GeneratedRidePdf
    {
        $html = $this->templateRenderer->render($data);

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
