<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Contracts;

interface SleeperInterface
{
    public function sleep(int $seconds): void;
}