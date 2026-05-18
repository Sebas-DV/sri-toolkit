<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\Workflow;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Signer\Signer;
use MTZ\Toolkit\Tests\Support\FakeSleeper;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use MTZ\Toolkit\Tests\Support\Signer\FakeClock;
use MTZ\Toolkit\Tests\Support\Signer\FakeIdGenerator;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\XMLMaker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class CompleteWorkflowTest extends TestCase
{
    /**
     * @throws DOMException|SoapFault
     */
    #[Test]
    public function it_generates_signs_and_sends_an_invoice_xml(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $accessKey = $this->generateAccessKey();

            $xml = $this->generateInvoiceXml($accessKey);

            $this->assertStringContainsString('<factura id="comprobante" version="1.1.0">', $xml);
            $this->assertStringContainsString("<claveAcceso>{$accessKey}</claveAcceso>", $xml);

            $signedXml = (new Signer(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
                clock: new FakeClock(),
                idGenerator: new FakeIdGenerator(),
            ))
                ->loadXml($xml)
                ->sign();

            $this->assertStringContainsString('<ds:Signature', $signedXml);
            $this->assertStringContainsString('<ds:SignatureValue', $signedXml);
            $this->assertStringContainsString('<xades:SignedProperties', $signedXml);

            $document = new DOMDocument();

            $this->assertTrue($document->loadXML($signedXml));

            $fakeSleeper = new FakeSleeper();

            $fakeSoapClient = new FakeSoapClient(
                receptionResponses: [
                    self::receptionResponse('RECIBIDA'),
                ],
                authorizationResponses: [
                    self::authorizationResponse(
                        status: 'AUTORIZADO',
                        accessKey: $accessKey,
                        xml: $signedXml,
                        authorizationDate: '2026-05-13T10:30:00-05:00',
                    ),
                ],
            );

            $sender = new Sender(
                config: new SenderConfig(
                    maxAttempts: 1,
                    sendDelay: 0,
                ),
                responseParser: new ResponseParser(),
                soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
                sleeper: $fakeSleeper,
            );

            $result = $sender->send(
                accessKey: $accessKey,
                signedXml: $signedXml,
            );

            $this->assertTrue($result->success);
            $this->assertTrue($result->receptionResult?->success);
            $this->assertTrue($result->authorizationResult?->success);

            $this->assertSame([$signedXml], $fakeSoapClient->receivedXmls);
            $this->assertSame([$accessKey], $fakeSoapClient->authorizedAccessKeys);

            $this->assertNotNull($result->authorizationResult);
            $this->assertNotNull($result->authorizationResult->authorizedDocument);

            $this->assertSame($signedXml, $result->authorizationResult->authorizedDocument->xml);
        } finally
        {
            $certificate->cleanup();
        }
    }

    private function generateAccessKey(): string
    {
        $accessKey = (new AccessKeyGenerator())->generate(
            AccessKeyData::make(
                emissionDate: '2026-05-13',
                documentType: DocumentType::Invoice,
                ruc: '1790012345001',
                environment: Environment::Testing,
                sequential: 25,
                numericCode: '12345678',
                establishmentCode: '001',
                emissionPointCode: '001',
            ),
        );

        $this->assertMatchesRegularExpression('/^\d{49}$/', $accessKey);

        return $accessKey;
    }

    private function generateInvoiceXml(string $accessKey): string
    {
        return (new XMLMaker())->generate(
            XmlGenerationData::make(
                documentType: XmlDocumentType::Invoice,
                environment: XmlEnvironment::Testing,
                accessKey: $accessKey,
                data: $this->invoicePayload(),
            ),
        )->toString();
    }

    private function invoicePayload(): array
    {
        return [
            'date' => '13/05/2026',
            'sequential' => '000000025',
            'company' => [
                'ruc' => '1790012345001',
                'legal_name' => 'MTZ TEST S.A.',
                'trade_name' => 'MTZ TEST',
                'head_office_address' => 'Quito',
            ],
            'establishment' => [
                'code' => '001',
            ],
            'emission_point' => [
                'code' => '001',
            ],
            'customer' => [
                'identification_type' => '05',
                'identification_number' => '1710034065',
                'name' => 'CONSUMIDOR FINAL',
                'address' => 'Quito',
            ],
            'establishment_address' => 'Quito',
            'requires_accounting' => 'NO',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'tax_totals' => [
                [
                    'code' => '2',
                    'percentage_code' => '4',
                    'taxable_base' => '10.00',
                    'value' => '1.50',
                ],
            ],
            'tip' => '0.00',
            'total_amount' => '11.50',
            'currency' => 'DOLAR',
            'payments' => [
                [
                    'method' => '01',
                    'total' => '11.50',
                ],
            ],
            'details' => [
                [
                    'main_code' => 'P001',
                    'description' => 'Producto de prueba',
                    'quantity' => '1.00',
                    'unit_price' => '10.00',
                    'discount' => '0.00',
                    'total_without_tax' => '10.00',
                    'taxes' => [
                        [
                            'code' => '2',
                            'percentage_code' => '4',
                            'rate' => '15.00',
                            'taxable_base' => '10.00',
                            'value' => '1.50',
                        ],
                    ],
                ],
            ],
            'additional_info' => [
                'Email' => 'cliente@example.com',
            ],
        ];
    }

    private static function receptionResponse(string $status): object
    {
        return (object) [
            'RespuestaRecepcionComprobante' => (object) [
                'estado' => $status,
                'comprobantes' => (object) [
                    'comprobante' => (object) [
                        'mensajes' => [],
                    ],
                ],
            ],
        ];
    }

    private static function authorizationResponse(
        string $status,
        ?string $accessKey = null,
        ?string $xml = null,
        ?string $authorizationDate = null,
        array $messages = [],
    ): object {
        return (object) [
            'RespuestaAutorizacionComprobante' => (object) [
                'autorizaciones' => (object) [
                    'autorizacion' => (object) [
                        'estado' => $status,
                        'numeroAutorizacion' => $accessKey,
                        'comprobante' => $xml,
                        'fechaAutorizacion' => $authorizationDate,
                        'mensajes' => $messages,
                    ],
                ],
            ],
        ];
    }
}
