<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Catalogs\Data;

/**
 * Single catalog entry used by XML payload codes.
 */
final readonly class CatalogEntry
{
    public function __construct(
        public string $code,
        public string $description,
        public ?float $rate = null,
    ) {
    }

    /**
     * @param array{code: string, description: string, rate?: int|float|null} $data
     */
    public static function fromArray(array $data): self
    {
        $rate = $data['rate'] ?? null;

        return new self(
            code: $data['code'],
            description: $data['description'],
            rate: is_int($rate) || is_float($rate) ? (float) $rate : null,
        );
    }

    /**
     * @return array{code: string, description: string, rate?: float}
     */
    public function toArray(): array
    {
        $data = [
            'code' => $this->code,
            'description' => $this->description,
        ];

        if ($this->rate !== null)
        {
            $data['rate'] = $this->rate;
        }

        return $data;
    }
}
