<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

/**
 * Abstraction for obtaining the current date/time.
 *
 * Allows injection of custom or mock clock implementations for testing.
 */
interface ClockInterface
{
    /**
     * Return the current date and time formatted as a string.
     *
     * @param string $timezone A valid timezone identifier (e.g. 'America/Guayaquil').
     * @return string The formatted date/time string.
     */
    public function now(string $timezone): string;
}
