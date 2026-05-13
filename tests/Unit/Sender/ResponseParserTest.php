<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Enums\ReceptionStatus;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use PHPUnit\Framework\TestCase;

final class ResponseParserTest extends TestCase
{
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

    public function it_parses_a_returned_reception_response_with_messages(): void
    {
        $parser = new ResponseParser();

        $response = self::receptionResponse('DEVUELTA', [
            self::message(
                type: 'ERROR',
                code: '43',
                message: 'CLAVE ACCESO REGISTRADA',
                additionalInformmation: 'La clave de acceso ya ha sido registrada'
            )
        ]);

        $messages = $parser->receptionMessage($response);

        $this->assertSame(ReceptionStatus::Returned, $parser->receptionStatus($response));
        $this->assertFalse($parser->isReceptionSuccessful($response));

        $this->assertCount(1, $messages);
        $this->assertSame('ERROR', $messages[0]->type);
        $this->assertSame('43', $messages[0]->code);
        $this->assertSame('CLAVE ACCESO REGISTRADA', $messages[0]->message);
        $this->assertSame('La clave de acceso ya ha sido registrada', $messages[0]->additionalInformation);
    }
}