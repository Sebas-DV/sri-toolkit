<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\AccessKeyGenerator;

use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccessKeyGeneratorTest extends TestCase
{
    #[Test]
    public function it_appends_the_check_digit_to_the_access_key_base(): void
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

        $this->assertSame(49, strlen($accessKey));
        $this->assertStringStartsWith(
            '130520260117900123450011001001000000025123456781',
            $accessKey,
        );
        $this->assertStringEndsWith('7', $accessKey);
    }
}
