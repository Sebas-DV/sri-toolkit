<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Data;

final readonly class GeneratedRidePdf
{
    public function __construct(
        public string $content,
        public string $filename,
    ) {
    }

    public function saveTo(string $path): void
    {
        file_put_contents($path, $this->content);
    }

    public function toString(): string
    {
        return $this->content;
    }
}
