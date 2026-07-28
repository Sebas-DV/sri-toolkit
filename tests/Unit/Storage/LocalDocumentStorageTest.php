<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Storage;

use MTZ\Toolkit\Storage\Exceptions\StorageException;
use MTZ\Toolkit\Storage\LocalDocumentStorage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocalDocumentStorageTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/mtz-storage-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    #[Test]
    public function it_writes_and_reads_content_creating_nested_directories(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $path = 'sri/documents/owner/2026/05/ACCESSKEY/ride.pdf';
        $storage->put($path, '%PDF-1.4 content');

        $this->assertFileExists($this->basePath . '/' . $path);
        $this->assertSame('%PDF-1.4 content', $storage->get($path));
    }

    #[Test]
    public function it_reports_existence_and_deletes_content(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $path = 'sri/documents/owner/file.xml';
        $storage->put($path, '<xml/>');

        $this->assertTrue($storage->exists($path));

        $storage->delete($path);

        $this->assertFalse($storage->exists($path));
    }

    #[Test]
    public function it_treats_deleting_a_missing_file_as_a_no_op(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $storage->delete('sri/documents/owner/missing.xml');

        $this->assertFalse($storage->exists('sri/documents/owner/missing.xml'));
    }

    #[Test]
    public function it_throws_when_reading_a_missing_file(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $this->expectException(StorageException::class);

        $storage->get('sri/documents/owner/missing.xml');
    }

    #[Test]
    public function it_normalizes_leading_slashes_and_backslashes(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $storage->put('/sri\\documents\\owner/file.xml', 'data');

        $this->assertSame('data', $storage->get('sri/documents/owner/file.xml'));
    }

    #[Test]
    public function it_rejects_path_traversal(): void
    {
        $storage = new LocalDocumentStorage($this->basePath);

        $this->expectException(StorageException::class);

        $storage->put('sri/../../etc/passwd', 'data');
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory))
        {
            return;
        }

        $items = scandir($directory) ?: [];

        foreach ($items as $item)
        {
            if ($item === '.' || $item === '..')
            {
                continue;
            }

            $path = $directory . '/' . $item;

            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
