<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Catalogs;

use BadMethodCallException;
use MTZ\Toolkit\Catalogs\Data\CatalogEntry;
use MTZ\Toolkit\Catalogs\Data\CatalogMetadata;

/**
 * @method list<CatalogEntry> list(string $catalogName)
 */
final class CatalogRegistry
{
    /**
     * @var array<string, array{entries: array<string, CatalogEntry>, meta: CatalogMetadata}>
     */
    private array $catalogs = [];

    /**
     * @var array<string, array{entries: array<string, CatalogEntry>, meta: CatalogMetadata}>
     */
    private array $defaults = [];

    /**
     * @param array<array-key, array{code: string, description: string, rate?: int|float|null}|CatalogEntry> $entries
     */
    public function registerDefault(string $name, array $entries, CatalogMetadata $meta): void
    {
        $normalized = $this->normalizeEntries($entries);
        $data = [
            'entries' => $normalized,
            'meta' => $meta,
        ];

        $this->catalogs[$name] = $data;
        $this->defaults[$name] = $data;
    }

    public function get(string $catalogName, string $code): ?CatalogEntry
    {
        return $this->catalogs[$catalogName]['entries'][$code] ?? null;
    }

    /**
     * Returns all entries in a catalog.
     *
     * PHP reserves "list" as a language construct, so the declared method is named
     * entries(). Calls to $registry->list(...) are still supported through __call().
     *
     * @return list<CatalogEntry>
     */
    public function entries(string $catalogName): array
    {
        return array_values($this->catalogs[$catalogName]['entries'] ?? []);
    }

    public function getMeta(string $catalogName): ?CatalogMetadata
    {
        return $this->catalogs[$catalogName]['meta'] ?? null;
    }

    /**
     * @return list<string>
     */
    public function listCatalogs(): array
    {
        return array_keys($this->catalogs);
    }

    /**
     * @param array<array-key, array{code: string, description: string, rate?: int|float|null}|CatalogEntry> $entries
     * @param array{source?: string, updatedAt?: string, notes?: string|null} $meta
     */
    public function override(string $catalogName, array $entries, array $meta = []): void
    {
        if (! isset($this->catalogs[$catalogName]))
        {
            throw new BadMethodCallException("Catalog \"{$catalogName}\" not found. Register it first.");
        }

        foreach ($this->normalizeEntries($entries) as $code => $entry)
        {
            $this->catalogs[$catalogName]['entries'][$code] = $entry;
        }

        $currentMeta = $this->catalogs[$catalogName]['meta'];
        $source = $meta['source'] ?? $currentMeta->source;
        $updatedAt = $meta['updatedAt'] ?? $currentMeta->updatedAt;

        if ($meta === [])
        {
            $source = 'Manual override';
            $updatedAt = date(DATE_ATOM);
        }

        $this->catalogs[$catalogName]['meta'] = new CatalogMetadata(
            source: $source,
            updatedAt: $updatedAt,
            notes: array_key_exists('notes', $meta) ? $meta['notes'] : $currentMeta->notes,
        );
    }

    public function reset(string $catalogName): void
    {
        if (isset($this->defaults[$catalogName]))
        {
            $this->catalogs[$catalogName] = $this->defaults[$catalogName];
        }
    }

    public function resetAll(): void
    {
        foreach (array_keys($this->defaults) as $catalogName)
        {
            $this->reset($catalogName);
        }
    }

    /**
     * @param list<mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'list')
        {
            $catalogName = $arguments[0] ?? null;

            if (! is_string($catalogName))
            {
                throw new BadMethodCallException('Catalog name must be a string.');
            }

            return $this->entries($catalogName);
        }

        throw new BadMethodCallException("Method {$name} does not exist.");
    }

    /**
     * @param array<array-key, array{code: string, description: string, rate?: int|float|null}|CatalogEntry> $entries
     *
     * @return array<string, CatalogEntry>
     */
    private function normalizeEntries(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $entry)
        {
            $catalogEntry = $entry instanceof CatalogEntry ? $entry : CatalogEntry::fromArray($entry);
            $normalized[$catalogEntry->code] = $catalogEntry;
        }

        return $normalized;
    }
}
