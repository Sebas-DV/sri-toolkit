<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Data\Message;
use MTZ\Toolkit\Sender\Enums\SriMessageCode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SriMessageCodeTest extends TestCase
{
    #[Test]
    public function it_classifies_a_retryable_error(): void
    {
        $code = SriMessageCode::CalculationDifferences;

        $this->assertSame('52', $code->value);
        $this->assertTrue($code->isRetryable());
        $this->assertFalse($code->isImpediment());
        $this->assertFalse($code->isWarning());
        $this->assertFalse($code->isProcessing());
    }

    #[Test]
    public function it_classifies_an_impediment(): void
    {
        $code = SriMessageCode::RucClosedByAuthority;

        $this->assertTrue($code->isImpediment());
        $this->assertFalse($code->isRetryable());
    }

    #[Test]
    public function it_classifies_processing(): void
    {
        $code = SriMessageCode::AccessKeyInProcessing;

        $this->assertTrue($code->isProcessing());
        $this->assertFalse($code->isRetryable());
    }

    #[Test]
    public function it_classifies_a_warning(): void
    {
        $code = SriMessageCode::TestEnvironment;

        $this->assertTrue($code->isWarning());
        $this->assertFalse($code->isRetryable());
    }

    #[Test]
    public function it_exposes_a_description_for_every_case(): void
    {
        foreach (SriMessageCode::cases() as $case)
        {
            $this->assertNotSame('', $case->description());
        }
    }

    #[Test]
    public function it_resolves_the_typed_code_from_a_message(): void
    {
        $message = new Message('ERROR', '70', 'CLAVE DE ACCESO EN PROCESAMIENTO', '');

        $this->assertSame(SriMessageCode::AccessKeyInProcessing, $message->sriCode());
        $this->assertTrue($message->sriCode()->isProcessing());

        $unknown = new Message('ERROR', '9999', 'Unknown', '');
        $this->assertNull($unknown->sriCode());
    }
}
