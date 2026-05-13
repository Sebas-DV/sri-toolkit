<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\GenerateAccessKey;

use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GenerateAccessKeyTest extends TestCase
{
    #[Test]
    public function it_generates_a_valid_sri_access_key(): void
    {
        $generator = new AccessKeyGenerator();

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

        $accessKey = $generator->generate($data);

        $this->assertSame('1305202601179001234500110010010000000251234567817', $accessKey);

        $this->assertSame(49, strlen($accessKey));
    }
}