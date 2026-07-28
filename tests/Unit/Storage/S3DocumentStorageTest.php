<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Storage;

use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Utils;
use MTZ\Toolkit\Storage\S3DocumentStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class S3DocumentStorageTest extends TestCase
{
    private MockHandler $mock;

    protected function setUp(): void
    {
        $this->mock = new MockHandler();
    }

    #[Test]
    public function it_puts_an_object_with_bucket_key_and_body(): void
    {
        $this->mock->append(new Result([]));

        $storage = new S3DocumentStorage($this->client(), 'my-bucket');
        $storage->put('sri/documents/owner/ride.pdf', '%PDF');

        $command = $this->mock->getLastCommand();

        $this->assertSame('PutObject', $command->getName());
        $this->assertSame('my-bucket', $command['Bucket']);
        $this->assertSame('sri/documents/owner/ride.pdf', $command['Key']);
        $this->assertSame('%PDF', $command['Body']);
    }

    #[Test]
    public function it_prefixes_the_key_when_a_prefix_is_configured(): void
    {
        $this->mock->append(new Result([]));

        $storage = new S3DocumentStorage($this->client(), 'my-bucket', 'tenant-a/');
        $storage->put('sri/documents/owner/ride.pdf', '%PDF');

        $this->assertSame('tenant-a/sri/documents/owner/ride.pdf', $this->mock->getLastCommand()['Key']);
    }

    #[Test]
    public function it_gets_the_object_body(): void
    {
        $this->mock->append(new Result(['Body' => Utils::streamFor('<xml/>')]));

        $storage = new S3DocumentStorage($this->client(), 'my-bucket');

        $this->assertSame('<xml/>', $storage->get('sri/documents/owner/file.xml'));
    }

    #[Test]
    public function it_deletes_an_object(): void
    {
        $this->mock->append(new Result([]));

        $storage = new S3DocumentStorage($this->client(), 'my-bucket');
        $storage->delete('sri/documents/owner/file.xml');

        $command = $this->mock->getLastCommand();

        $this->assertSame('DeleteObject', $command->getName());
        $this->assertSame('sri/documents/owner/file.xml', $command['Key']);
    }

    #[Test]
    public function it_builds_a_temporary_presigned_url(): void
    {
        $storage = new S3DocumentStorage($this->client(), 'my-bucket');

        $url = $storage->temporaryUrl(
            'sri/documents/owner/ride.pdf',
            new DateTimeImmutable('+10 minutes'),
        );

        $this->assertStringContainsString('sri/documents/owner/ride.pdf', $url);
        $this->assertStringContainsString('X-Amz-Signature', $url);
    }

    private function client(): S3Client
    {
        return new S3Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'],
            'handler' => $this->mock,
        ]);
    }
}
