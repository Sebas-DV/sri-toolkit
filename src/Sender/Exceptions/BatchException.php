<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

/**
 * Raised when a batch (lote) violates the SRI size or count limits.
 */
final class BatchException extends SenderException
{
    /** @var int Maximum number of vouchers per batch. */
    public const MAX_VOUCHERS = 50;

    /** @var int Maximum batch size in bytes (500 kB). */
    public const MAX_SIZE_BYTES = 512000;

    public static function empty(): self
    {
        return new self('A batch must contain at least one voucher.');
    }

    public static function tooManyVouchers(int $count): self
    {
        return new self(sprintf('A batch may contain at most %d vouchers, %d given.', self::MAX_VOUCHERS, $count));
    }

    public static function tooLarge(int $bytes): self
    {
        return new self(sprintf('A batch may be at most %d bytes, %d given.', self::MAX_SIZE_BYTES, $bytes));
    }
}
