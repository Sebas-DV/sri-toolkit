<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Renders;

use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateRendererInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

final readonly class TwigRideTemplateRenderer implements RideTemplateRendererInterface
{
    private Environment $twig;

    public function __construct(
        private RidePdfConfig $config = new RidePdfConfig(),
    ) {
        $templatesPath = $this->config->templatesPath
            ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';

        $loader = new FilesystemLoader($templatesPath);

        $this->twig = new Environment($loader, [
            'autoescape' => 'html',
            'strict_variables' => true,
            'cache' => false,
            'auto_reload' => true,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function render(RideData $data): string
    {
        return $this->twig->render(
            $this->template($data->documentType),
            [
                'ride' => $data,
                'data' => $data->data,
                'accessKey' => $data->accessKey,
                'authorizationNumber' => $data->authorizationNumber ?? $data->accessKey,
                'authorizationDate' => $data->authorizationDate,
                'documentTitle' => $data->documentType->title(),
                'documentNumber' => $this->documentNumber($data),
                'company' => $data->data['company'] ?? [],
                'accessKeyBarcode' => $this->barcodeDataUri($data->accessKey),
            ],
        );
    }

    private function barcodeDataUri(string $value): string
    {
        $barcode = (new TypeCode128())->getBarcode($value);
        $svg = (new SvgRenderer())->render($barcode, 410, 46);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function documentNumber(RideData $data): string
    {
        $establishmentCode = (string) ($data->data['establishment_code'] ?? '001');
        $emissionPointCode = (string) ($data->data['emission_point_code'] ?? '001');
        $sequential = (string) ($data->data['sequential'] ?? '');

        if ($sequential === '')
        {
            $sequential = substr($data->accessKey, 30, 9) ?: '000000000';
        }

        return $establishmentCode . '-' . $emissionPointCode . '-' . $sequential;
    }

    private function template(RideDocumentType $documentType): string
    {
        return match ($documentType)
        {
            RideDocumentType::Invoice => 'invoice.html.twig',
            RideDocumentType::CreditNote => 'credit-note.html.twig',
            RideDocumentType::DebitNote => 'debit-note.html.twig',
            RideDocumentType::WithholdingReceipt => 'withholding-receipt.html.twig',
            RideDocumentType::DeliveryGuide => 'delivery-guide.html.twig',
            RideDocumentType::PurchaseSettlement => 'purchase-settlement.html.twig',
        };
    }
}
