<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\AccessKeyGenerator;

use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;
use MTZ\Toolkit\AccessKeyGenerator\Services\CheckDigitCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CheckDigitCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_the_check_digit_using_digit_calculator(): void
    {
        $calculator = new CheckDigitCalculator();

        $base = '130520260117900123450011001001000000025123456781';

        $this->assertSame(7, $calculator->calculate($base));
    }

    #[Test]
    public function it_fails_when_access_key_base_has_less_than_48_digits(): void
    {
        $calculator = new CheckDigitCalculator();

        $this->expectException(AccessKeyException::class);

        $calculator->calculate('123456789');
    }

    #[Test]
    public function it_fails_when_access_key_base_contains_letters(): void
    {
        $calculator = new CheckDigitCalculator();

        $this->expectException(AccessKeyException::class);

        $calculator->calculate('13052026011790012345001100100100000002512345678A');
    }
}