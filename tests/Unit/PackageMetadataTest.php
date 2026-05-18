<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PackageMetadataTest extends TestCase
{
    #[Test]
    public function it_exposes_packagist_discovery_metadata(): void
    {
        $composer = $this->composerJson();

        $this->assertSame('matiz-studio-creative/sri-toolkit', $this->stringValue($composer['name'] ?? null));
        $this->assertSame('MIT', $this->stringValue($composer['license'] ?? null));
        $this->assertSame('https://sri-toolkit.matizstudiocreative.com', $this->stringValue($composer['homepage'] ?? null));

        $keywords = $this->stringList($composer['keywords'] ?? null);

        $this->assertContains('sri', $keywords);
        $this->assertContains('sri-ecuador', $keywords);
        $this->assertContains('facturacion-electronica', $keywords);
        $this->assertContains('guia-remision', $keywords);
        $this->assertContains('comprobante-retencion', $keywords);
    }

    #[Test]
    public function it_contains_the_mit_license_file(): void
    {
        $license = file_get_contents(__DIR__ . '/../../LICENSE');

        if ($license === false)
        {
            throw new RuntimeException('Unable to read LICENSE file.');
        }

        $this->assertStringContainsString('MIT License', $license);
        $this->assertStringContainsString('Copyright (c) 2026 Matiz Studio Creative', $license);
        $this->assertStringContainsString('Permission is hereby granted, free of charge', $license);
    }

    /**
     * @return array<string, mixed>
     */
    private function composerJson(): array
    {
        $contents = file_get_contents(__DIR__ . '/../../composer.json');

        if ($contents === false)
        {
            throw new RuntimeException('Unable to read composer.json.');
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded))
        {
            throw new RuntimeException('composer.json must decode to an object.');
        }

        return $this->stringKeyMap($decoded);
    }

    private function stringValue(mixed $value): string
    {
        if (! is_string($value))
        {
            throw new RuntimeException('Expected string metadata value.');
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value))
        {
            throw new RuntimeException('Expected string list metadata value.');
        }

        $items = [];

        foreach ($value as $item)
        {
            if (! is_string($item))
            {
                throw new RuntimeException('Expected string metadata list item.');
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<mixed, mixed> $value
     *
     * @return array<string, mixed>
     */
    private function stringKeyMap(array $value): array
    {
        $items = [];

        foreach ($value as $key => $item)
        {
            if (! is_string($key))
            {
                throw new RuntimeException('Expected string metadata key.');
            }

            $items[$key] = $item;
        }

        return $items;
    }
}
