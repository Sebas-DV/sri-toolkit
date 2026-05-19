<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Catalogs\Data;

/**
 * Provenance metadata for a catalog.
 */
final readonly class CatalogMetadata
{
    public function __construct(
        public string $source,
        public string $updatedAt,
        public ?string $notes = null,
    ) {
    }

    /**
     * @return array{source: string, updatedAt: string, notes?: string}
     */
    public function toArray(): array
    {
        $data = [
            'source' => $this->source,
            'updatedAt' => $this->updatedAt,
        ];

        if ($this->notes !== null)
        {
            $data['notes'] = $this->notes;
        }

        return $data;
    }
}
