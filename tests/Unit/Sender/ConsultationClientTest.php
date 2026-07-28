<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Clients\ConsultationClient;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\ConsultationStatus;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class ConsultationClientTest extends TestCase
{
    private const ACCESS_KEY = '1305202601179001234500110010010000000251234567817';

    #[Test]
    public function it_reports_an_authorized_document(): void
    {
        $client = $this->client(new FakeSoapClient(consultationResponses: [
            self::stateResponse(
                estadoAutorizacion: 'AUTORIZADO',
                documentType: 'Factura',
                issuerRuc: '1760013210001',
                authorizationDate: '2024-12-12T10:49:37-05:00',
            ),
        ]));

        $result = $client->query(self::ACCESS_KEY);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isAuthorized());
        $this->assertSame(ConsultationStatus::Authorized, $result->status);
        $this->assertSame('Factura', $result->documentType);
        $this->assertSame('1760013210001', $result->issuerRuc);
        $this->assertSame('2024-12-12T10:49:37-05:00', $result->authorizationDate);
    }

    #[Test]
    public function it_reports_an_annulled_document(): void
    {
        $client = $this->client(new FakeSoapClient(consultationResponses: [
            self::stateResponse(estadoAutorizacion: 'ANULADO'),
        ]));

        $result = $client->query(self::ACCESS_KEY);

        $this->assertTrue($result->success);
        $this->assertTrue($result->isAnnulled());
        $this->assertFalse($result->isAuthorized());
    }

    #[Test]
    public function it_returns_failure_when_the_query_is_rejected(): void
    {
        $fakeSoapClient = new FakeSoapClient(consultationResponses: [
            (object) [
                'EstadoAutorizacionComprobante' => (object) [
                    'estadoConsulta' => 'RECHAZADA',
                    'claveAcceso' => self::ACCESS_KEY,
                    'mensajes' => (object) [
                        'mensaje' => (object) [
                            'identificador' => '99',
                            'mensaje' => 'ERROR AL CONSULTAR DATOS DEL SERVICIO WEB',
                            'informacionAdicional' => 'No existen datos para los parámetros ingresados',
                            'tipo' => 'ERROR',
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->client($fakeSoapClient)->query(self::ACCESS_KEY);

        $this->assertFalse($result->success);
        $this->assertSame(ConsultationStatus::Rejected, $result->status);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('ERROR AL CONSULTAR', $result->error);
        $this->assertSame([self::ACCESS_KEY], $fakeSoapClient->consultedAccessKeys);
    }

    #[Test]
    public function it_returns_failure_on_soap_fault(): void
    {
        $client = $this->client(new FakeSoapClient(consultationResponses: [
            new SoapFault('Server', 'Connection failed'),
        ]));

        $result = $client->query(self::ACCESS_KEY);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Connection failed', $result->error);
    }

    #[Test]
    public function it_fails_when_access_key_is_invalid(): void
    {
        $this->expectException(InvalidAccessKeyException::class);

        $this->client(new FakeSoapClient())->query('123');
    }

    private function client(FakeSoapClient $fakeSoapClient): ConsultationClient
    {
        return new ConsultationClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
        );
    }

    private static function stateResponse(
        string $estadoAutorizacion,
        ?string $documentType = null,
        ?string $issuerRuc = null,
        ?string $authorizationDate = null,
    ): object {
        return (object) [
            'EstadoAutorizacionComprobante' => (object) [
                'claveAcceso' => self::ACCESS_KEY,
                'estadoAutorizacion' => $estadoAutorizacion,
                'tipoComprobante' => $documentType,
                'rucEmisor' => $issuerRuc,
                'fechaAutorizacion' => $authorizationDate,
            ],
        ];
    }
}
