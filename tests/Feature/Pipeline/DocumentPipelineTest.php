<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\Pipeline;

use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Pipeline\Data\DocumentEmission;
use MTZ\Toolkit\Pipeline\DocumentPipeline;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Tests\Support\FakeSleeper;
use MTZ\Toolkit\Tests\Support\FakeSoapClient;
use MTZ\Toolkit\Tests\Support\FakeSoapClientFactory;
use MTZ\Toolkit\Tests\Support\Pipeline\FakeDocumentSigner;
use MTZ\Toolkit\Tests\Support\Storage\InMemoryDocumentStorage;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\Tests\Support\XMLMaker\StubSchemaValidator;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocumentPipelineTest extends TestCase
{
    private const ACCESS_KEY = '1305202601179001234500110010010000000251234567817';

    #[Test]
    public function it_generates_signs_sends_rides_and_stores_every_artifact(): void
    {
        $storage = new InMemoryDocumentStorage();
        $signer = new FakeDocumentSigner();

        $pipeline = new DocumentPipeline(
            signer: $signer,
            sender: $this->sender($this->soapClient(authorized: true)),
            rideGenerator: new RideGenerator(),
            documentStore: new DocumentStore($storage),
            validator: new XsdValidator(),
        );

        $result = $pipeline->emit($this->emission());

        $this->assertTrue($result->success);
        $this->assertSame(self::ACCESS_KEY, $result->accessKey);
        $this->assertSame([$result->generatedXml], $signer->signed);
        $this->assertNotNull($result->signedXml);
        $this->assertTrue($result->send?->success);
        $this->assertNotNull($result->ride);
        $this->assertStringStartsWith('%PDF', $result->ride->content);

        $this->assertSame(
            [
                'generated.xml',
                'signed.xml',
                'reception-response.json',
                'authorization-response.json',
                'authorized.xml',
                'ride.pdf',
            ],
            array_keys($result->storedPaths),
        );

        foreach ($result->storedPaths as $path)
        {
            $this->assertArrayHasKey($path, $storage->files);
        }
    }

    #[Test]
    public function it_gates_on_schema_validation_and_does_not_sign_or_store(): void
    {
        $storage = new InMemoryDocumentStorage();
        $signer = new FakeDocumentSigner();

        $pipeline = new DocumentPipeline(
            signer: $signer,
            documentStore: new DocumentStore($storage),
            validator: new StubSchemaValidator(['invalid element order']),
        );

        $result = $pipeline->emit($this->emission());

        $this->assertFalse($result->success);
        $this->assertSame(['invalid element order'], $result->schemaErrors);
        $this->assertNotNull($result->error);
        $this->assertSame([], $signer->signed);
        $this->assertNull($result->signedXml);
        $this->assertSame([], $result->storedPaths);
        $this->assertSame([], $storage->files);
    }

    #[Test]
    public function it_reports_send_failure_and_skips_the_ride(): void
    {
        $storage = new InMemoryDocumentStorage();

        $pipeline = new DocumentPipeline(
            signer: new FakeDocumentSigner(),
            sender: $this->sender($this->soapClient(authorized: false)),
            rideGenerator: new RideGenerator(),
            documentStore: new DocumentStore($storage),
            validator: new XsdValidator(),
        );

        $result = $pipeline->emit($this->emission());

        $this->assertFalse($result->success);
        $this->assertFalse($result->send?->success);
        $this->assertNull($result->ride);
        $this->assertNotNull($result->error);

        $this->assertArrayHasKey('generated.xml', $result->storedPaths);
        $this->assertArrayHasKey('signed.xml', $result->storedPaths);
        $this->assertArrayHasKey('reception-response.json', $result->storedPaths);
        $this->assertArrayNotHasKey('authorized.xml', $result->storedPaths);
        $this->assertArrayNotHasKey('ride.pdf', $result->storedPaths);
    }

    #[Test]
    public function it_runs_generate_validate_ride_and_store_without_signer_or_sender(): void
    {
        $storage = new InMemoryDocumentStorage();

        $pipeline = new DocumentPipeline(
            rideGenerator: new RideGenerator(),
            documentStore: new DocumentStore($storage),
            validator: new XsdValidator(),
        );

        $result = $pipeline->emit($this->emission());

        $this->assertTrue($result->success);
        $this->assertNull($result->signedXml);
        $this->assertNull($result->send);
        $this->assertNotNull($result->ride);
        $this->assertSame(['generated.xml', 'ride.pdf'], array_keys($result->storedPaths));
    }

    private function emission(): DocumentEmission
    {
        $invoice = SampleXmlPayloads::invoice();

        return new DocumentEmission(
            xml: $invoice,
            ride: RideData::make(
                documentType: RideDocumentType::Invoice,
                accessKey: self::ACCESS_KEY,
                data: $invoice->data,
            ),
            ownerKey: '1790012345001',
        );
    }

    private function sender(FakeSoapClient $client): Sender
    {
        return new Sender(
            config: new SenderConfig(maxAttempts: 1, sendDelay: 0),
            responseParser: new ResponseParser(),
            soapClientFactory: new FakeSoapClientFactory($client),
            sleeper: new FakeSleeper(),
        );
    }

    private function soapClient(bool $authorized): FakeSoapClient
    {
        if (! $authorized)
        {
            return new FakeSoapClient(
                receptionResponses: [$this->receptionResponse('DEVUELTA', [
                    $this->message('ERROR', '43', 'CLAVE ACCESO REGISTRADA'),
                ])],
                authorizationResponses: [],
            );
        }

        return new FakeSoapClient(
            receptionResponses: [$this->receptionResponse('RECIBIDA')],
            authorizationResponses: [$this->authorizationResponse(
                'AUTORIZADO',
                self::ACCESS_KEY,
                '<factura>Autorizada</factura>',
                '2026-05-13T10:30:00-05:00',
            )],
        );
    }

    /**
     * @param list<object> $messages
     */
    private function receptionResponse(string $status, array $messages = []): object
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

    private function authorizationResponse(
        string $status,
        string $accessKey,
        string $xml,
        string $authorizationDate,
    ): object {
        return (object) [
            'RespuestaAutorizacionComprobante' => (object) [
                'autorizaciones' => (object) [
                    'autorizacion' => (object) [
                        'estado' => $status,
                        'numeroAutorizacion' => $accessKey,
                        'comprobante' => $xml,
                        'fechaAutorizacion' => $authorizationDate,
                        'mensajes' => [],
                    ],
                ],
            ],
        ];
    }

    private function message(string $type, string $code, string $message): object
    {
        return (object) [
            'tipo' => $type,
            'identificador' => $code,
            'mensaje' => $message,
            'informacionAdicional' => '',
        ];
    }
}
