<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Support;

use MTZ\Toolkit\Sender\Contracts\SleeperInterface;

/**
 * Native implementation of the sleeper contract using PHP's built-in sleep function.
 *
 * Provides a trivial wrapper around PHP's native sleep() for use in retry logic.
 */
final class NativeSleeper implements SleeperInterface
{
    /**
     * Pauses execution for the given number of seconds.
     *
     * Only sleeps if the provided value is greater than zero.
     *
     * @param int $seconds The number of seconds to sleep.
     * @return void
     */
    public function sleep(int $seconds): void
    {
        if ($seconds > 0)
        {
            sleep($seconds);
        }
    }
}
