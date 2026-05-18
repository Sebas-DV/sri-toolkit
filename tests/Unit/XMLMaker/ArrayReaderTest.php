<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArrayReaderTest extends TestCase
{
    #[Test]
    public function it_reads_required_string_values(): void
    {
        $reader = new ArrayReader([
            'name' => 'MTZ',
        ]);

        $this->assertSame('MTZ', $reader->string('name'));
    }

    #[Test]
    public function it_fails_when_required_string_is_missing(): void
    {
        $this->expectException(InvalidXmlDataException::class);

        (new ArrayReader([]))->string('name');
    }

    #[Test]
    public function it_reads_optional_string_values(): void
    {
        $reader = new ArrayReader([]);

        $this->assertNull($reader->nullableString('trade_name'));
    }

    #[Test]
    public function it_reads_required_array_values(): void
    {
        $reader = new ArrayReader([
            'company' => [
                'ruc' => '1790012345001',
            ],
        ]);

        $this->assertSame(['ruc' => '1790012345001'], $reader->array('company'));
    }

    #[Test]
    public function it_fails_when_required_array_is_not_array(): void
    {
        $this->expectException(InvalidXmlDataException::class);

        (new ArrayReader([
            'company' => 'invalid',
        ]))->array('company');
    }
}
