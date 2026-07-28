<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Clients\AuthorizationClient;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSleeper;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class AuthorizationClientTest extends TestCase
{
    private const ACCESS_KEY = '1305202601179001234500110010010000000251234567817';

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_authorizes_an_access_key_successfully(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            authorizationResponses: [
                self::authorizationResponse(
                    status: 'AUTORIZADO',
                    accessKey: self::ACCESS_KEY,
                    xml: '<factura>Autorizada</factura>',
                    authorizationDate: '2026-05-13T10:30:00-05:00',
                ),
            ],
        );

        $client = new AuthorizationClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: new FakeSleeper(),
        );

        $result = $client->authorize(self::ACCESS_KEY);

        $this->assertTrue($result->success);
        $this->assertSame(AuthorizationStatus::Authorized, $result->status);
        $this->assertSame(1, $result->attempts);
        $this->assertSame('<factura>Autorizada</factura>', $result->authorizedDocument?->xml);
        $this->assertSame([self::ACCESS_KEY], $fakeSoapClient->authorizedAccessKeys);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_retries_while_processing_until_the_document_is_authorized(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            authorizationResponses: [
                self::authorizationResponse(status: 'EN PROCESO'),
                self::authorizationResponse(
                    status: 'AUTORIZADO',
                    accessKey: self::ACCESS_KEY,
                    xml: '<factura>Autorizada</factura>',
                    authorizationDate: '2026-05-13T10:30:00-05:00',
                ),
            ],
        );

        $client = new AuthorizationClient(
            config: new SenderConfig(
                maxAttempts: 2,
                retryDelay: 1,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $client->authorize(self::ACCESS_KEY);

        $this->assertTrue($result->success);
        $this->assertSame(2, $result->attempts);
        $this->assertSame([1], $fakeSleeper->sleepSeconds);
        $this->assertCount(2, $fakeSoapClient->authorizedAccessKeys);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_fails_fast_on_a_definitive_rejection_without_retrying(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            authorizationResponses: [
                self::authorizationResponse(status: 'NO AUTORIZADO', messages: [
                    self::message(
                        type: 'ERROR',
                        code: '70',
                        message: 'ERROR EN FIRMA',
                        additionalInformation: 'La firma del comprobante no es válida',
                    ),
                ]),
                self::authorizationResponse(
                    status: 'AUTORIZADO',
                    accessKey: self::ACCESS_KEY,
                    xml: '<factura>Autorizada</factura>',
                ),
            ],
        );

        $client = new AuthorizationClient(
            config: new SenderConfig(
                maxAttempts: 3,
                retryDelay: 1,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $client->authorize(self::ACCESS_KEY);

        $this->assertFalse($result->success);
        $this->assertSame(AuthorizationStatus::NotAuthorized, $result->status);
        $this->assertSame(1, $result->attempts);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('ERROR EN FIRMA', $result->error);
        $this->assertSame([], $fakeSleeper->sleepSeconds);
        $this->assertCount(1, $fakeSoapClient->authorizedAccessKeys);
    }

    #[Test]
    public function it_returns_failure_after_max_attempts_while_still_processing(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            authorizationResponses: [
                self::authorizationResponse(status: 'EN PROCESO'),
                self::authorizationResponse(status: 'EN PROCESO'),
            ],
        );

        $client = new AuthorizationClient(
            config: new SenderConfig(
                maxAttempts: 2,
                retryDelay: 1,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $client->authorize(self::ACCESS_KEY);

        $this->assertFalse($result->success);
        $this->assertSame(AuthorizationStatus::Processing, $result->status);
        $this->assertSame(2, $result->attempts);
        $this->assertSame([1], $fakeSleeper->sleepSeconds);
        $this->assertCount(2, $fakeSoapClient->authorizedAccessKeys);
    }

    /**
     * @throws SoapFault
     */
    #[Test]
    public function it_returns_failure_when_soap_fails_after_max_attempts(): void
    {
        $fakeSleeper = new FakeSleeper();

        $fakeSoapClient = new FakeSoapClient(
            authorizationResponses: [
                new SoapFault('Server', 'Connection failed'),
                new SoapFault('Server', 'Connection failed again'),
            ],
        );

        $client = new AuthorizationClient(
            config: new SenderConfig(
                maxAttempts: 2,
                retryDelay: 1,
            ),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: $fakeSleeper,
        );

        $result = $client->authorize(self::ACCESS_KEY);

        $this->assertFalse($result->success);
        $this->assertNull($result->status);
        $this->assertSame(2, $result->attempts);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Connection failed', $result->error);
        $this->assertSame([1], $fakeSleeper->sleepSeconds);
    }

    #[Test]
    public function it_fails_when_access_key_is_invalid(): void
    {
        $client = new AuthorizationClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory(new FakeSoapClient()),
            sleeper: new FakeSleeper(),
        );

        $this->expectException(InvalidAccessKeyException::class);

        $client->authorize('123');
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
