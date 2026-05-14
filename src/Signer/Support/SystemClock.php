<?php

namespace MTZ\Toolkit\Signer\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MTZ\Toolkit\Signer\Contract\ClockInterface;

class SystemClock implements ClockInterface
{
    /**
     * @throws Exception
     */
    public function now(string $timezone): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($timezone)))
            ->format('Y-m-d\TH:i:sP');
    }
}
