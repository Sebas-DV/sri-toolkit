<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Renders;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Data\GeneratedRidePdf;
use MTZ\Toolkit\RideGenerator\Exceptions\RideGeneratorException;

final readonly class MpdfRideRenderer
{
    public function __construct(
        private RidePdfConfig $config = new RidePdfConfig(),
    ) {
    }

    public function render(string $html, string $fileName): GeneratedRidePdf
    {
        try
        {
            $mpdf = new Mpdf([
                'format' => $this->config->format,
                'orientation' => $this->config->orientation,
                'margin_top' => $this->config->marginTop,
                'margin_bottom' => $this->config->marginBottom,
                'margin_left' => $this->config->marginLeft,
                'margin_right' => $this->config->marginRight,
                'default_font' => $this->config->defaultFont,
                'tempDir' => $this->config->tempDir !== '' ? $this->config->tempDir : sys_get_temp_dir(),
            ]);

            $mpdf->WriteHTML($html);

            return new GeneratedRidePdf(
                content: $mpdf->output('', 'S'),
                filename: $fileName,
            );
        } catch (MpdfException $e)
        {
            throw new RideGeneratorException('Could not generate RIDE PDF: ' . $e->getMessage(), previous: $e);
        }
    }
}
