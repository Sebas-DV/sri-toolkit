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

            if (! is_dir($tempDir) && ! mkdir($tempDir, 0775, true) && ! is_dir($tempDir))
            {
                throw new RideGeneratorException('Could not create the temporary PDF directory.');
            }

            $options = new Options();
            $options->setDefaultFont($this->config->defaultFont);
            $options->setDefaultPaperSize($this->config->format);
            $options->setDefaultPaperOrientation($this->orientation());
            $options->setTempDir($tempDir);
            $options->setIsRemoteEnabled(false);
            $options->setIsHtml5ParserEnabled(true);
            $options->setChroot([
                dirname(__DIR__, 3),
                $tempDir,
            ]);

            $dompdf = new Dompdf($options);
            $dompdf->setPaper($this->config->format, $this->orientation());
            $dompdf->loadHtml($this->withPageMargins($html), 'UTF-8');
            $dompdf->render();

            return new GeneratedRidePdf(
                content: $dompdf->output(['compress' => 1]),
                filename: $fileName,
            );
        } catch (Throwable $exception)
        {
            throw new RideGeneratorException(
                'Could not generate RIDE PDF: ' . $exception->getMessage(),
                previous: $exception,
            );
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
