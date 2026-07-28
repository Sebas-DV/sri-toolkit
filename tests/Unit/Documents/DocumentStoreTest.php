<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Documents;

use DateTimeImmutable;
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Tests\Support\Storage\InMemoryDocumentStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocumentStoreTest extends TestCase
{
    private InMemoryDocumentStorage $storage;

    private DocumentStore $store;

    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->storage = new InMemoryDocumentStorage();
        $this->store = new DocumentStore($this->storage);
        $this->date = new DateTimeImmutable('2026-05-13');
    }

    #[Test]
    public function it_stores_each_artifact_under_a_partitioned_path(): void
    {
        $owner = '1790012345001';
        $accessKey = '1305202601179001234500110010010000000251234567817';

        $cases = [
            'sri/documents/1790012345001/2026/05/' . $accessKey . '/generated.xml'
                => $this->store->putGeneratedXml($owner, $this->date, $accessKey, '<factura/>'),
            'sri/documents/1790012345001/2026/05/' . $accessKey . '/signed.xml'
                => $this->store->putSignedXml($owner, $this->date, $accessKey, '<factura signed/>'),
            'sri/documents/1790012345001/2026/05/' . $accessKey . '/authorized.xml'
                => $this->store->putAuthorizedXml($owner, $this->date, $accessKey, '<factura auth/>'),
            'sri/documents/1790012345001/2026/05/' . $accessKey . '/ride.pdf'
                => $this->store->putRidePdf($owner, $this->date, $accessKey, '%PDF'),
        ];

        foreach ($cases as $expectedPath => $returnedPath)
        {
            $this->assertSame($expectedPath, $returnedPath);
            $this->assertArrayHasKey($expectedPath, $this->storage->files);
        }
    }

    #[Test]
    public function it_stores_the_ride_pdf_contents(): void
    {
        $path = $this->store->putRidePdf('owner', $this->date, 'ACCESSKEY', '%PDF-1.4 bytes');

        $this->assertSame('%PDF-1.4 bytes', $this->storage->get($path));
    }

    #[Test]
    public function it_encodes_response_payloads_as_json(): void
    {
        $path = $this->store->putReceptionResponse('owner', $this->date, 'ACCESSKEY', [
            'estado' => 'RECIBIDA',
            'mensajes' => [],
        ]);

        $this->assertStringEndsWith('reception-response.json', $path);
        $this->assertSame(
            ['estado' => 'RECIBIDA', 'mensajes' => []],
            json_decode($this->storage->get($path), true),
        );
    }

    #[Test]
    public function it_sanitizes_owner_and_access_key_segments(): void
    {
        $path = $this->store->putSignedXml('owner/../evil', $this->date, 'AB/CD 12', '<xml/>');

        $this->assertSame('sri/documents/owner-..-evil/2026/05/AB-CD-12/signed.xml', $path);
    }

    #[Test]
    public function it_reads_back_through_the_storage(): void
    {
        $path = $this->store->putGeneratedXml('owner', $this->date, 'ACCESSKEY', '<factura/>');

        $this->assertSame('<factura/>', $this->store->get($path));
    }
}
