<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Validation;

/**
 * Validates Ecuadorian identification numbers (cédula and RUC).
 *
 * Applies the official check-digit algorithms so invalid buyer/provider/subject
 * identifications are caught before emission (SRI warnings 59 and 62):
 *  - Cédula: 10 digits, modulo 10.
 *  - RUC: 13 digits; natural persons reuse the cédula check, public entities use
 *    modulo 11 with an 8-digit base, and private/foreign entities use modulo 11
 *    with a 9-digit base.
 */
final class IdentificationValidator
{
    /** @var string SRI identification type: RUC. */
    public const TYPE_RUC = '04';

    /** @var string SRI identification type: cédula. */
    public const TYPE_CEDULA = '05';

    /** @var string SRI identification type: passport. */
    public const TYPE_PASSPORT = '06';

    /** @var string SRI identification type: final consumer. */
    public const TYPE_FINAL_CONSUMER = '07';

    /** @var string SRI identification type: foreign identification. */
    public const TYPE_FOREIGN = '08';

    /**
     * Validates an identification for a given SRI identification type.
     *
     * Passport and foreign identifications are free-form (only non-empty is
     * required); final consumer is always accepted. When no type is provided the
     * number is validated by inferring cédula/RUC from its length.
     *
     * @param string $number The identification number.
     * @param string|null $type The SRI identification type code, or null to infer.
     * @return bool
     */
    public function isValid(string $number, ?string $type = null): bool
    {
        return match ($type)
        {
            self::TYPE_RUC => $this->isValidRuc($number),
            self::TYPE_CEDULA => $this->isValidCedula($number),
            self::TYPE_FINAL_CONSUMER => true,
            self::TYPE_PASSPORT, self::TYPE_FOREIGN => $number !== '',
            default => $this->inferAndValidate($number),
        };
    }

    /**
     * Validates a 10-digit cédula using the modulo 10 algorithm.
     *
     * @param string $cedula The cédula number.
     * @return bool
     */
    public function isValidCedula(string $cedula): bool
    {
        if (in_array(preg_match('/^\d{10}$/', $cedula), [0, false], true))
        {
            return false;
        }

        if (! $this->hasValidProvince($cedula))
        {
            return false;
        }

        if ((int) $cedula[2] > 5)
        {
            return false;
        }

        $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $sum = 0;

        foreach ($coefficients as $i => $coefficient)
        {
            $product = ((int) $cedula[$i]) * $coefficient;
            $sum += $product > 9 ? $product - 9 : $product;
        }

        $verifier = (10 - ($sum % 10)) % 10;

        return $verifier === (int) $cedula[9];
    }

    /**
     * Validates a 13-digit RUC, dispatching on the taxpayer type (third digit).
     *
     * @param string $ruc The RUC number.
     * @return bool
     */
    public function isValidRuc(string $ruc): bool
    {
        if (in_array(preg_match('/^\d{13}$/', $ruc), [0, false], true))
        {
            return false;
        }

        if (! $this->hasValidProvince($ruc))
        {
            return false;
        }

        if ((int) substr($ruc, 10, 3) < 1)
        {
            return false;
        }

        $thirdDigit = (int) $ruc[2];

        if ($thirdDigit <= 5)
        {
            return $this->isValidCedula(substr($ruc, 0, 10));
        }

        if ($thirdDigit === 6)
        {
            return $this->hasValidModulo11CheckDigit($ruc, [3, 2, 7, 6, 5, 4, 3, 2], 8);
        }

        if ($thirdDigit === 9)
        {
            return $this->hasValidModulo11CheckDigit($ruc, [4, 3, 2, 7, 6, 5, 4, 3, 2], 9);
        }

        return false;
    }

    private function inferAndValidate(string $number): bool
    {
        return match (strlen($number))
        {
            10 => $this->isValidCedula($number),
            13 => $this->isValidRuc($number),
            default => false,
        };
    }

    private function hasValidProvince(string $number): bool
    {
        $province = (int) substr($number, 0, 2);

        return ($province >= 1 && $province <= 24) || $province === 30;
    }

    /**
     * @param list<int> $coefficients Coefficients applied to the leading digits.
     * @param int $checkIndex Index of the check digit.
     */
    private function hasValidModulo11CheckDigit(string $number, array $coefficients, int $checkIndex): bool
    {
        $sum = 0;

        foreach ($coefficients as $i => $coefficient)
        {
            $sum += ((int) $number[$i]) * $coefficient;
        }

        $residue = $sum % 11;
        $verifier = $residue === 0 ? 0 : 11 - $residue;

        if ($verifier === 10)
        {
            return false;
        }

        return $verifier === (int) $number[$checkIndex];
    }
}
