<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Catalogs;

use BadMethodCallException;
use MTZ\Toolkit\Catalogs\Catalogs;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CatalogRegistryTest extends TestCase
{
    #[Test]
    public function it_registers_common_catalogs(): void
    {
        $catalogs = Catalogs::freshRegistry()->listCatalogs();

        $this->assertContains('vat-rates', $catalogs);
        $this->assertContains('vat-withholding', $catalogs);
        $this->assertContains('payment-methods', $catalogs);
        $this->assertContains('identification-types', $catalogs);
        $this->assertContains('document-types', $catalogs);
        $this->assertContains('ice-rates', $catalogs);
    }

    #[Test]
    public function it_lists_payment_methods_with_the_compatible_list_call(): void
    {
        $payments = Catalogs::freshRegistry()->list('payment-methods');

        $this->assertCount(8, $payments);
        $this->assertSame('01', $payments[0]->code);
        $this->assertSame('No financial system used', $payments[0]->description);
    }

    #[Test]
    public function it_exposes_current_iva_and_retention_codes(): void
    {
        $registry = Catalogs::freshRegistry();

        $this->assertSame(15.0, $registry->get('vat-rates', '4')?->rate);
        $this->assertSame(5.0, $registry->get('vat-rates', '5')?->rate);
        $this->assertSame(13.0, $registry->get('vat-rates', '10')?->rate);
        $this->assertSame(10.0, $registry->get('vat-withholding', '9')?->rate);
        $this->assertSame(50.0, $registry->get('vat-withholding', '11')?->rate);
        $this->assertSame(100.0, $registry->get('vat-withholding', '3')?->rate);
    }

    #[Test]
    public function it_overrides_and_resets_catalog_entries(): void
    {
        $registry = Catalogs::freshRegistry();

        $registry->override('vat-rates', [
            '4' => ['code' => '4', 'description' => 'VAT 16%', 'rate' => 16],
        ], [
            'source' => 'Custom update',
            'updatedAt' => '2026-06-01',
        ]);

        $this->assertSame(16.0, $registry->get('vat-rates', '4')?->rate);
        $this->assertSame('Custom update', $registry->getMeta('vat-rates')?->source);

        $registry->reset('vat-rates');

        $this->assertSame(15.0, $registry->get('vat-rates', '4')?->rate);
    }

    #[Test]
    public function it_rejects_overrides_for_unknown_catalogs(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Catalog "unknown" not found');

        Catalogs::freshRegistry()->override('unknown', [
            '1' => ['code' => '1', 'description' => 'Test'],
        ]);
    }
}
