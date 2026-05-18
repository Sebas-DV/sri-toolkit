<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\AccessKeyGenerator;

use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccessKeyDataTest extends TestCase
{
    #[Test]
    public function it_builds_the_48_digit_access_key_base(): void
    {
        $data = AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345001',
            environment: Environment::Testing,
            sequential: 25,
            numericCode: '12345678',
            establishmentCode: '001',
            emissionPointCode: '001',
        );

        $this->assertSame(
            '130520260117900123450011001001000000025123456781',
            $data->toAccessKeyBase(),
        );

        $this->assertSame(48, strlen($data->toAccessKeyBase()));
    }

    #[Test]
    public function it_pads_the_sequential_to_9_digits(): void
    {
        $data = AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345001',
            environment: Environment::Testing,
            sequential: 25,
            numericCode: '12345678',
        );

        $this->assertStringContainsString('000000025', $data->toAccessKeyBase());
    }

    #[Test]
    public function it_fails_when_ruc_has_invalid_length(): void
    {
        $this->expectException(AccessKeyException::class);

        AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345',
            environment: Environment::Testing,
            sequential: 25,
            numericCode: '12345678',
        );
    }

    #[Test]
    public function it_fails_when_ruc_contains_letters(): void
    {
        $this->expectException(AccessKeyException::class);

        AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345ABC',
            environment: Environment::Testing,
            sequential: 25,
            numericCode: '12345678',
        );
    }

    #[Test]
    public function it_fails_when_sequential_exceeds_9_digits(): void
    {
        $this->expectException(AccessKeyException::class);

        AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345001',
            environment: Environment::Testing,
            sequential: '1234567890',
            numericCode: '12345678',
        );
    }

    #[Test]
    public function it_fails_when_numeric_code_has_invalid_length(): void
    {
        $this->expectException(AccessKeyException::class);

        AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: DocumentType::Invoice,
            ruc: '1790012345001',
            environment: Environment::Testing,
            sequential: 25,
            numericCode: '1234',
        );
    }
}
