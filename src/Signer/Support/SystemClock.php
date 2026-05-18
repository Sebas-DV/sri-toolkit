<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MTZ\Toolkit\Signer\Contract\ClockInterface;

/**
 * System clock implementation returning the current wall-clock time.
 */
class SystemClock implements ClockInterface
{
    /**
     * Return the current date and time in ISO 8601 format.
     *
     * @param string $timezone A valid timezone identifier.
     * @return string The formatted date/time string.
     * @throws Exception When the timezone is invalid.
     */
    public function now(string $timezone): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($timezone)))
            ->format('Y-m-d\TH:i:sP');
    }
}
