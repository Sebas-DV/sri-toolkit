<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Support;

use MTZ\Toolkit\Sender\Contracts\SleeperInterface;

final class NativeSleeper implements SleeperInterface
{
    public function sleep(int $seconds): void
    {
        if ($seconds > 0)
        {
            sleep($seconds);
        }
    }
}
