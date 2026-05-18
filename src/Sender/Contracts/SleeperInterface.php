<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Contracts;

/**
 * Contract for a sleep mechanism used during retry delays.
 *
 * Implementations should pause execution for the given number of seconds.
 */
interface SleeperInterface
{
    /**
     * Pauses execution for a specified number of seconds.
     *
     * @param int $seconds The number of seconds to sleep.
     * @return void
     */
    public function sleep(int $seconds): void;
}
