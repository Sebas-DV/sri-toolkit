<?php

declare(strict_types=1);

use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;

final class FakeIdGenerator implements IdGeneratorInterface
{
    private int $index = 0;

    public function generate(): string
    {
        $this->index++;

        return 'fake-id-' . $this->index;
    }
}