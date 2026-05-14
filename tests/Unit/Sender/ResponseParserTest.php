<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;
use MTZ\Toolkit\Sender\Enums\ReceptionStatus;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseParserTest extends TestCase
{
    #[Test]
    public function it_parses_a_successful_reception_response(): void
    {
        $parser = new ResponseParser();

        $response = self::receptionResponse('RECIBIDA');

        $this->assertSame(
            ReceptionStatus::Received,
            $parser->receptionStatus($response)
        );

        $this->assertTrue($parser->isReceptionSuccessful($response));
    }

    #[Test]
    public function it_parses_a_returned_reception_response_with_messages(): void
    {
        $parser = new ResponseParser();

        $response = self::receptionResponse('DEVUELTA', [
            self::message(
                type: 'ERROR',
                code: '43',
                message: 'CLAVE ACCESO REGISTRADA',
                additionalInformation: 'La clave de acceso ya ha sido registrada'
            )
        ]);

        $messages = $parser->receptionMessage($response);

        $this->assertSame(ReceptionStatus::Returned, $parser->receptionStatus($response));
        $this->assertFalse($parser->isReceptionSuccessful($response));

        $this->assertCount(1, $messages);
        $this->assertSame('ERROR', $messages[0]->type);
        $this->assertSame('43', $messages[0]->code);
        $this->assertSame('CLAVE ACCESO REGISTRADA', $messages[0]->message);
    }

    #[Test]
    public function it_parses_a_successful_authorization_response(): void
    {
        $parser = new ResponseParser();

        $response = self::authorizationResponse(
            status: 'AUTORIZADO',
            accessKey: '1305202601179001234500110010010000000251234567817',
            xml: '<factura>Autorizada</factura>',
            authorizationDate: '2026-05-13T10:30:00-05:00'
        );

        $document = $parser->authorizedDocument($response);

        $this->assertSame(
            AuthorizationStatus::Authorized,
            $parser->authorizationStatus($response)
        );

        $this->assertTrue($parser->isAuthorizationSuccessful($response));
        $this->assertSame('1305202601179001234500110010010000000251234567817', $document?->accessKey);
        $this->assertSame('<factura>Autorizada</factura>', $document?->xml);
        $this->assertSame('2026-05-13T10:30:00-05:00', $document?->authorizationDate);
    }

    #[Test]
    public function it_parses_authorization_messages(): void
    {
        $parser = new ResponseParser();

        $response = self::authorizationResponse(
            status: 'NO AUTORIZADO',
            messages: [
                self::message(
                    type: 'ERROR',
                    code: '70',
                    message: 'ERROR EN FIRMA',
                    additionalInformation: 'Firma invalida'
                )
            ]
        );

        $messages = $parser->authorizationMessages($response);

        $this->assertSame(AuthorizationStatus::NotAuthorized, $parser->authorizationStatus($response));
        $this->assertFalse($parser->isAuthorizationSuccessful($response));

        $this->assertCount(1, $messages);
        $this->assertSame('ERROR', $messages[0]->type);
        $this->assertSame('70', $messages[0]->code);
        $this->assertSame('ERROR EN FIRMA', $messages[0]->message);
    }

    public function receptionResponse(string $status, array $messages = []): object
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

    private static function authorizationResponse(
        string $status,
        ?string $accessKey = null,
        ?string $xml = null,
        ?string $authorizationDate = null,
        array $messages = []
    ): object
    {
        return (object) [
            'RespuestaAutorizacionComprobante' => (object) [
                'autorizaciones' => (object) [
                    'autorizacion' => (object) [
                        'estado' => $status,
                        'numeroAutorizacion' => $accessKey,
                        'comprobante' => $xml,
                        'fechaAutorizacion' => $authorizationDate,
                        'mensajes' => $messages
                    ]
                ]
            ]
        ];
    }

    private static function message(
        string $type,
        string $code,
        string $message,
        string $additionalInformation = '',
    ): object
    {
        return (object) [
            'tipo' => $type,
            'identificador' => $code,
            'mensaje' => $message,
            'informacionAdicional' => $additionalInformation,
        ];
    }
}