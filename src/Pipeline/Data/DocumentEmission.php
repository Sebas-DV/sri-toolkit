<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Pipeline\Data;

use DateTimeImmutable;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;

/**
 * Input for a single run of the document pipeline.
 *
 * Carries the XML generation payload plus optional RIDE data and the storage
 * owner key. Storage runs only when both a DocumentStore and an owner key are
 * present; RIDE generation runs only when RideData is provided.
 */
final readonly class DocumentEmission
{
    /**
     * @param XmlGenerationData $xml The payload used to build the XML document.
     * @param RideData|null $ride Optional RIDE payload; when set, a RIDE PDF is generated.
     * @param string|null $ownerKey Optional storage owner key (e.g. the emitter RUC); enables storage.
     * @param DateTimeImmutable|null $storedAt Optional storage timestamp; defaults to now when storing.
     */
    public function __construct(
        public XmlGenerationData $xml,
        public ?RideData $ride = null,
        public ?string $ownerKey = null,
        public ?DateTimeImmutable $storedAt = null,
    ) {
    }

    public static function make(
        XmlGenerationData $xml,
        ?RideData $ride = null,
        ?string $ownerKey = null,
        ?DateTimeImmutable $storedAt = null,
    ): self {
        return new self($xml, $ride, $ownerKey, $storedAt);
    }
}
