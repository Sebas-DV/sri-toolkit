<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\Sender;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSleeper;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class SenderTest extends TestCase
{
    private const ACCESS_KEY = '1305202601179001234500110010010000000251234567817';

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_sends_and_authorizes_a_signed_xml_successfully(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                self::receptionResponse(status: 'RECIBIDA'),
            ],
            authorizationResponses: [
                self::authorizationResponse(
                    status: 'AUTORIZADO',
                    accessKey: self::ACCESS_KEY,
                    xml: '<factura>Autorizada</factura>',
                    authorizationDate: '2026-05-13T10:30:00-05:00',
                ),
            ],
        );

        $sender = new Sender(
            config: new SenderConfig(
                maxAttempts: 1,
                sendDelay: 3,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $sender->send(
            accessKey: self::ACCESS_KEY,
            signedXml: '<facturaFirmada/>',
        );

        $this->assertTrue($result->success);
        $this->assertTrue($result->receptionResult?->success);
        $this->assertTrue($result->authorizationResult?->success);
        $this->assertSame(['<facturaFirmada/>'], $fakeSoapClient->receivedXmls);
        $this->assertSame([self::ACCESS_KEY], $fakeSoapClient->authorizedAccessKeys);
        $this->assertSame([3], $fakeSleeper->sleepSeconds);

        $this->assertNotNull($result->authorizationResult);
        $this->assertNotNull($result->authorizationResult->authorizedDocument);

        $this->assertSame(
            '<factura>Autorizada</factura>',
            $result->authorizationResult->authorizedDocument->xml,
        );
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_stops_when_reception_fails(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                self::receptionResponse('DEVUELTA', [
                    self::message(
                        type: 'ERROR',
                        code: '43',
                        message: 'CLAVE ACCESO REGISTRADA',
                        additionalInformation: 'El comprobante ya ha sido recibido',
                    ),
                ]),
            ],
            authorizationResponses: [
                self::authorizationResponse(status: 'AUTORIZADO'),
            ],
        );

        $sender = new Sender(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $sender->send(
            accessKey: self::ACCESS_KEY,
            signedXml: '<facturaFirmada/>',
        );

        $this->assertFalse($result->success);

        $this->assertFalse($result->receptionResult?->success);
        $this->assertNull($result->authorizationResult);

        $this->assertSame(['<facturaFirmada/>'], $fakeSoapClient->receivedXmls);
        $this->assertSame([], $fakeSoapClient->authorizedAccessKeys);

        $this->assertSame([], $fakeSleeper->sleepSeconds);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('CLAVE ACCESO REGISTRADA', $result->error);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_fails_when_authorization_fails_after_reception_success(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [
                self::receptionResponse('RECIBIDA'),
            ],
            authorizationResponses: [
                self::authorizationResponse('NO AUTORIZADO', messages: [
                    self::message(
                        type: 'ERROR',
                        code: '70',
                        message: 'ERROR EN FIRMA',
                        additionalInformation: 'Firma invalida',
                    ),
                ]),
            ],
        );

        $sender = new Sender(
            config: new SenderConfig(
                maxAttempts: 1,
                sendDelay: 3,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $sender->send(
            accessKey: self::ACCESS_KEY,
            signedXml: '<facturaFirmada/>',
        );

        $this->assertFalse($result->success);
        $this->assertTrue($result->receptionResult?->success);
        $this->assertFalse($result->authorizationResult?->success);
        $this->assertSame([3], $fakeSleeper->sleepSeconds);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('ERROR EN FIRMA', $result->error);
    }

    private static function receptionResponse(string $status, array $messages = []): object
    {
        return (object) [
            'RespuestaRecepcionComprobante' => (object) [
                'estado' => $status,
                'comprobantes' => (object) [
                    'comprobante' => (object) [
                        'mensajes' => $messages,
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

    private static function message(
        string $type,
        string $code,
        string $message,
        string $additionalInformation = '',
    ): object {
        return (object) [
            'tipo' => $type,
            'identificador' => $code,
            'mensaje' => $message,
            'informacionAdicional' => $additionalInformation,
        ];
    }
}
