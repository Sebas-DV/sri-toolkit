<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Renders;

use Dompdf\Dompdf;
use Dompdf\Options;
use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Data\GeneratedRidePdf;
use MTZ\Toolkit\RideGenerator\Exceptions\RideGeneratorException;
use Throwable;

final readonly class DompdfRideRenderer
{
    public function __construct(
        private RidePdfConfig $config = new RidePdfConfig(),
    ) {
    }

    public function render(string $html, string $fileName): GeneratedRidePdf
    {
        try
        {
            $tempDir = $this->config->tempDir !== '' ? $this->config->tempDir : sys_get_temp_dir();

            if (! is_dir($tempDir))
            {
                mkdir($tempDir, 0775, true);
            }

            $options = new Options();
            $options->setDefaultFont($this->config->defaultFont);
            $options->setDefaultPaperSize($this->config->format);
            $options->setDefaultPaperOrientation($this->orientation());
            $options->setTempDir($tempDir);
            $options->setIsRemoteEnabled(true);
            $options->setIsHtml5ParserEnabled(true);
            $options->setChroot(dirname(__DIR__, 3));

            $dompdf = new Dompdf($options);
            $dompdf->setPaper($this->config->format, $this->orientation());
            $dompdf->loadHtml($this->withPageMargins($html));
            $dompdf->render();

            return new GeneratedRidePdf(
                content: $dompdf->output(),
                filename: $fileName,
            );
        } catch (Throwable $e)
        {
            throw new RideGeneratorException('Could not generate RIDE PDF: ' . $e->getMessage(), previous: $e);
        }
    }

    private function orientation(): string
    {
        return strtoupper($this->config->orientation) === 'L' ? 'landscape' : 'portrait';
    }

    private function withPageMargins(string $html): string
    {
        $pageStyle = sprintf(
            '<style>@page { margin: %Fmm %Fmm %Fmm %Fmm; }</style>',
            $this->config->marginTop,
            $this->config->marginRight,
            $this->config->marginBottom,
            $this->config->marginLeft,
        );

        if (str_contains($html, '</head>'))
        {
            return str_replace('</head>', $pageStyle . '</head>', $html);
        }

        return $pageStyle . $html;
    }
}
