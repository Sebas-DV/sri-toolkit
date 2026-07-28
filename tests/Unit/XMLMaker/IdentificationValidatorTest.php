<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Validation\IdentificationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentificationValidatorTest extends TestCase
{
    private IdentificationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new IdentificationValidator();
    }

    #[Test]
    #[DataProvider('cedulas')]
    public function it_validates_cedulas(string $cedula, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidCedula($cedula));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function cedulas(): iterable
    {
        yield 'valid' => ['1710034065', true];
        yield 'wrong check digit' => ['1710034064', false];
        yield 'invalid province' => ['9910034065', false];
        yield 'third digit too high' => ['1760034065', false];
        yield 'too short' => ['171003406', false];
        yield 'non numeric' => ['17100A4065', false];
    }

    #[Test]
    #[DataProvider('rucs')]
    public function it_validates_rucs(string $ruc, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidRuc($ruc));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function rucs(): iterable
    {
        yield 'natural person' => ['1710034065001', true];
        yield 'private entity (third digit 9)' => ['1790012344001', true];
        yield 'public entity (third digit 6)' => ['1760013210001', true];
        yield 'wrong private check digit' => ['1790012345001', false];
        yield 'establishment below 001' => ['1710034065000', false];
        yield 'invalid third digit 7' => ['1770012344001', false];
        yield 'too short' => ['179001234400', false];
    }

    #[Test]
    public function it_dispatches_by_identification_type(): void
    {
        $this->assertTrue($this->validator->isValid('1710034065', IdentificationValidator::TYPE_CEDULA));
        $this->assertTrue($this->validator->isValid('1760013210001', IdentificationValidator::TYPE_RUC));
        $this->assertFalse($this->validator->isValid('1710034064', IdentificationValidator::TYPE_CEDULA));

        $this->assertTrue($this->validator->isValid('9999999999999', IdentificationValidator::TYPE_FINAL_CONSUMER));
        $this->assertTrue($this->validator->isValid('X1234567', IdentificationValidator::TYPE_PASSPORT));
        $this->assertFalse($this->validator->isValid('', IdentificationValidator::TYPE_FOREIGN));
    }

    #[Test]
    public function it_infers_type_from_length_when_not_provided(): void
    {
        $this->assertTrue($this->validator->isValid('1710034065'));
        $this->assertTrue($this->validator->isValid('1760013210001'));
        $this->assertFalse($this->validator->isValid('12345'));
    }
}
