<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Clients\ReceptionClient;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\ReceptionStatus;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class ReceptionClientTest extends TestCase
{
    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_validates_a_signed_xml_successfully(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                self::receptionResponse('RECIBIDA')
            ]
        );

        $client = new ReceptionClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient)
        );

        $result = $client->validate('<facturaFirmada />');

        $this->assertTrue($result->success);
        $this->assertSame(ReceptionStatus::Received, $result->status);
        $this->assertSame(['<facturaFirmada />'], $fakeSoapClient->receivedXmls);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_returns_failure_when_xml_is_returned_by_sri(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                self::receptionResponse('DEVUELTA', [
                    self::message(
                        type: 'ERROR',
                        code: '43',
                        message: 'CLAVE ACCESO REGISTRADA',
                        additionalInformation: 'La clave de acceso ya ha sido registrada'
                    )
                ])
            ]
        );

        $client = new ReceptionClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient)
        );

        $result = $client->validate('<facturaFirmada />');

        $this->assertFalse($result->success);
        $this->assertSame(ReceptionStatus::Returned, $result->status);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('CLAVE ACCESO REGISTRADA', $result->error);
        $this->assertCount(1, $result->messages);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_returns_failure_when_soap_throws_exception(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                new SoapFault('Server', 'Connection failed')
            ]
        );

        $client = new ReceptionClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient)
        );

        $result = $client->validate('<facturaFirmada />');

        $this->assertFalse($result->success);
        $this->assertNull($result->status);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Connection failed', $result->error);
    }

    public static function receptionResponse(string $status, array $messages = []): object
    {
        return (object) [
            'RespuestaRecepcionComprobante' => (object) [
                'estado' => $status,
                'comprobantes' => (object) [
                    'comprobante' => (object) [
                        'mensajes' => $messages
                    ]
                ]
            ]
        ];
    }

    public static function message(string $type, string $code, string $message, string $additionalInformation = ''): object
    {
        return (object) [
            'tipo' => $type,
            'identificador' => $code,
            'mensaje' => $message,
            'informacionAdicional' => $additionalInformation,
        ];
    }
}