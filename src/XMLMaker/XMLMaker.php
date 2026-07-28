<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Calculation\TotalsCalculator;
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Factories\XmlDocumentBuilderFactory;

/**
 * Main entry point for generating SRI-compliant XML documents.
 *
 * Orchestrates document builder selection via the factory and delegates
 * XML construction to the appropriate document-type-specific builder. When
 * enabled, it also derives the document totals from the detail lines so the XML
 * is always internally consistent.
 */
final readonly class XMLMaker
{
    /** @var list<XmlDocumentType> Document types whose totals are derived from detail lines. */
    private const DETAIL_BASED_TYPES = [
        XmlDocumentType::Invoice,
        XmlDocumentType::PurchaseSettlement,
        XmlDocumentType::CreditNote,
    ];

    private XmlDocumentBuilderFactory $builderFactory;

    /**
     * @param XmlMakerConfig $config Generation configuration.
     * @param XmlDocumentBuilderFactory|null $builderFactory Optional builder factory (built from config when null).
     * @param TotalsCalculator $totalsCalculator Service that derives totals from detail lines.
     */
    public function __construct(
        private XmlMakerConfig $config = new XmlMakerConfig(),
        ?XmlDocumentBuilderFactory $builderFactory = null,
        private TotalsCalculator $totalsCalculator = new TotalsCalculator(),
    ) {
        $this->builderFactory = $builderFactory ?? new XmlDocumentBuilderFactory($this->config);
    }

    /**
     * Generates an SRI XML document from the provided generation data.
     *
     * @param XmlGenerationData $data The document type, environment, access key, and payload.
     * @return GeneratedXml The fully built XML document wrapper.
     */
    public function generate(XmlGenerationData $data): GeneratedXml
    {
        $data = $this->withCalculatedTotals($data);

        return $this->builderFactory
            ->make($data->documentType)
            ->build($data);
    }

    /**
     * Derives and injects document totals from the detail lines when enabled.
     *
     * @param XmlGenerationData $data The original generation data.
     * @return XmlGenerationData The data enriched with computed totals, or the original.
     */
    private function withCalculatedTotals(XmlGenerationData $data): XmlGenerationData
    {
        if (! $this->config->calculateTotals)
        {
            return $data;
        }

        if (! in_array($data->documentType, self::DETAIL_BASED_TYPES, true))
        {
            return $data;
        }

        $details = $data->data['details'] ?? null;

        if (! is_array($details) || $details === [])
        {
            return $data;
        }

        $tip = isset($data->data['tip']) && is_numeric($data->data['tip'])
            ? (float) $data->data['tip']
            : 0.0;

        $totals = $this->totalsCalculator->calculate(array_values($details), $tip);

        return new XmlGenerationData(
            documentType: $data->documentType,
            environment: $data->environment,
            accessKey: $data->accessKey,
            data: array_merge($data->data, $totals->toArray()),
        );
    }
}
