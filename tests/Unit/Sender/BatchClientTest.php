<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Clients\BatchClient;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoapFault;

final class BatchClientTest extends TestCase
{
    private const LOTE_KEY = '1305202601179001234500110010010000000251234567817';

    #[Test]
    public function it_authorizes_every_voucher_in_the_batch(): void
    {
        $fakeSoapClient = new FakeSoapClient(batchAuthorizationResponses: [
            self::loteResponse([
                ['estado' => 'AUTORIZADO', 'xml' => '<factura>A</factura>'],
                ['estado' => 'AUTORIZADO', 'xml' => '<factura>B</factura>'],
            ]),
        ]);

        $result = $this->client($fakeSoapClient)->authorize(self::LOTE_KEY);

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->authorizations);
        $this->assertTrue($result->authorizations[0]->success);
        $this->assertSame('<factura>A</factura>', $result->authorizations[0]->authorizedDocument?->xml);
        $this->assertSame([self::LOTE_KEY], $fakeSoapClient->authorizedLoteAccessKeys);
    }

    #[Test]
    public function it_fails_when_any_voucher_is_not_authorized(): void
    {
        $result = $this->client(new FakeSoapClient(batchAuthorizationResponses: [
            self::loteResponse([
                ['estado' => 'AUTORIZADO', 'xml' => '<factura>A</factura>'],
                ['estado' => 'NO AUTORIZADO', 'messages' => [self::message('39', 'FIRMA INVÁLIDA')]],
            ]),
        ]))->authorize(self::LOTE_KEY);

        $this->assertFalse($result->success);
        $this->assertCount(2, $result->authorizations);
        $this->assertFalse($result->authorizations[1]->success);
        $this->assertNotNull($result->error);
    }

    #[Test]
    public function it_handles_a_single_voucher_response(): void
    {
        $result = $this->client(new FakeSoapClient(batchAuthorizationResponses: [
            (object) [
                'RespuestaAutorizacionLote' => (object) [
                    'claveAccesoLote' => self::LOTE_KEY,
                    'numeroComprobantes' => 1,
                    'autorizaciones' => (object) [
                        'autorizacion' => (object) [
                            'estado' => 'AUTORIZADO',
                            'comprobante' => '<factura>A</factura>',
                        ],
                    ],
                ],
            ],
        ]))->authorize(self::LOTE_KEY);

        $this->assertTrue($result->success);
        $this->assertCount(1, $result->authorizations);
    }

    #[Test]
    public function it_fails_when_no_vouchers_are_returned(): void
    {
        $result = $this->client(new FakeSoapClient(batchAuthorizationResponses: [
            (object) ['RespuestaAutorizacionLote' => (object) ['autorizaciones' => (object) []]],
        ]))->authorize(self::LOTE_KEY);

        $this->assertFalse($result->success);
        $this->assertSame([], $result->authorizations);
    }

    #[Test]
    public function it_returns_failure_on_soap_fault(): void
    {
        $result = $this->client(new FakeSoapClient(batchAuthorizationResponses: [
            new SoapFault('Server', 'Connection failed'),
        ]))->authorize(self::LOTE_KEY);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Connection failed', (string) $result->error);
    }

    #[Test]
    public function it_fails_when_access_key_is_invalid(): void
    {
        $this->expectException(InvalidAccessKeyException::class);

        $this->client(new FakeSoapClient())->authorize('123');
    }

    private function client(FakeSoapClient $fakeSoapClient): BatchClient
    {
        return new BatchClient(
            config: new SenderConfig(),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
        );
    }

    /**
     * @param list<array<string, mixed>> $states
     */
    private static function loteResponse(array $states): object
    {
        $authorizations = array_map(
            static fn (array $state): object => (object) [
                'estado' => $state['estado'],
                'numeroAutorizacion' => $state['accessKey'] ?? null,
                'comprobante' => $state['xml'] ?? null,
                'fechaAutorizacion' => $state['date'] ?? null,
                'mensajes' => $state['messages'] ?? [],
            ],
            $states,
        );

        return (object) [
            'RespuestaAutorizacionLote' => (object) [
                'claveAccesoLote' => self::LOTE_KEY,
                'numeroComprobantes' => count($states),
                'autorizaciones' => (object) ['autorizacion' => $authorizations],
            ],
        ];
    }

    private static function message(string $code, string $message): object
    {
        return (object) [
            'tipo' => 'ERROR',
            'identificador' => $code,
            'mensaje' => $message,
            'informacionAdicional' => '',
        ];
    }
}
