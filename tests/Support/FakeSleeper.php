<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support;

use MTZ\Toolkit\Sender\Contracts\SleeperInterface;

final class FakeSleeper implements SleeperInterface
{
    public array $sleepSeconds = [];

    public function sleep(int $seconds): void
    {
        $this->sleepSeconds[] = $seconds;
    }
}
