<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Signer;

final readonly class GeneratedCertificate
{
    public function __construct(
        public string $path,
        public string $password,
        public array $temporaryFiles = [],
        public ?string $temporaryDirectory = null,
    ) {
    }

    public function cleanup(): void
    {
        foreach (array_unique(array_merge([$this->path], $this->temporaryFiles)) as $file)
        {
            if (is_file($file))
            {
                @unlink($file);
            }
        }

        if ($this->temporaryDirectory !== null && is_dir($this->temporaryDirectory))
        {
            @rmdir($this->temporaryDirectory);
        }
    }
}
