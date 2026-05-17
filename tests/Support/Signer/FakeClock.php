<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Signer;

use MTZ\Toolkit\Signer\Contract\ClockInterface;

final class FakeClock implements ClockInterface
{
    public function now(string $timezone): string
    {
        return '2026-05-13T10:30:00-05:00';
    }
}