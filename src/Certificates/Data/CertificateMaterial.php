<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Certificates\Data;

final readonly class CertificateMaterial
{
    public function __construct(
        public string $path,
        public string $password,
        private bool $temporary = true,
    ) {
    }

    public function cleanup(): void
    {
        if ($this->temporary && is_file($this->path))
        {
            @unlink($this->path);
        }
    }
}
