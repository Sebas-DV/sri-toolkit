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

final class BatchSendTest extends TestCase
{
    private const LOTE_KEY = '1305202601179001234500110010010000000251234567817';

    #[Test]
    public function it_sends_and_authorizes_a_batch(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [$this->receptionResponse('RECIBIDA')],
            batchAuthorizationResponses: [$this->loteResponse(['AUTORIZADO', 'AUTORIZADO'])],
        );

        $result = $this->sender($fakeSoapClient)->sendBatch(
            self::LOTE_KEY,
            '1790012345001',
            ['<factura>A</factura>', '<factura>B</factura>'],
        );

        $this->assertTrue($result->success);
        $this->assertTrue($result->receptionResult?->success);
        $this->assertTrue($result->authorizationResult?->success);
        $this->assertCount(2, $result->authorizationResult->authorizations);
        $this->assertCount(1, $fakeSoapClient->receivedXmls);
        $this->assertSame([self::LOTE_KEY], $fakeSoapClient->authorizedLoteAccessKeys);
    }

    #[Test]
    public function it_stops_when_the_batch_reception_fails(): void
    {
        $fakeSoapClient = new FakeSoapClient(
            receptionResponses: [$this->receptionResponse('DEVUELTA')],
            batchAuthorizationResponses: [$this->loteResponse(['AUTORIZADO'])],
        );

        $result = $this->sender($fakeSoapClient)->sendBatch(
            self::LOTE_KEY,
            '1790012345001',
            ['<factura>A</factura>'],
        );

        $this->assertFalse($result->success);
        $this->assertFalse($result->receptionResult?->success);
        $this->assertNull($result->authorizationResult);
        $this->assertSame([], $fakeSoapClient->authorizedLoteAccessKeys);
    }

    private function sender(FakeSoapClient $fakeSoapClient): Sender
    {
        return new Sender(
            config: new SenderConfig(sendDelay: 0),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
            sleeper: new FakeSleeper(),
        );
    }

    private function receptionResponse(string $status): object
    {
        return (object) [
            'RespuestaRecepcionComprobante' => (object) [
                'estado' => $status,
                'comprobantes' => (object) [
                    'comprobante' => (object) ['mensajes' => []],
                ],
            ],
        ];
    }

    /**
     * @param list<string> $states
     */
    private function loteResponse(array $states): object
    {
        $authorizations = array_map(
            static fn (string $estado): object => (object) [
                'estado' => $estado,
                'comprobante' => '<factura>Autorizada</factura>',
                'mensajes' => [],
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
}
