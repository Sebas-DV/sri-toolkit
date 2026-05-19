<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Contracts;

use MTZ\Toolkit\RideGenerator\Data\RideData;

interface RideTemplateInterface
{
    public function render(RideData $data): string;
}
