<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Pipeline;

use DateTimeImmutable;
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Pipeline\Contracts\DocumentSignerInterface;
use MTZ\Toolkit\Pipeline\Data\DocumentEmission;
use MTZ\Toolkit\Pipeline\Data\EmissionResult;
use MTZ\Toolkit\RideGenerator\RideGenerator;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\XMLMaker\Validation\SchemaValidatorInterface;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
use MTZ\Toolkit\XMLMaker\XMLMaker;

/**
 * End-to-end orchestrator for an SRI electronic document.
 *
 * Runs, in order: generate XML, validate against the official XSD, sign, send
 * (reception + authorization), generate the RIDE PDF, and persist every
 * artifact. Each stage is optional: pass null to skip signing, sending, RIDE
 * generation, storage, or schema validation.
 */
final readonly class DocumentPipeline
{
    /**
     * @param DocumentSignerInterface|null $signer Signs the generated XML; skipped when null.
     * @param Sender|null $sender Sends the signed XML to the SRI; skipped when null.
     * @param RideGenerator|null $rideGenerator Generates the RIDE PDF; skipped when null or no RideData.
     * @param DocumentStore|null $documentStore Persists artifacts; skipped when null or no owner key.
     * @param SchemaValidatorInterface|null $validator Validates the generated XML; skipped when null.
     * @param XMLMaker $xmlMaker Builds the XML document.
     */
    public function __construct(
        private ?DocumentSignerInterface $signer = null,
        private ?Sender $sender = null,
        private ?RideGenerator $rideGenerator = null,
        private ?DocumentStore $documentStore = null,
        private ?SchemaValidatorInterface $validator = new XsdValidator(),
        private XMLMaker $xmlMaker = new XMLMaker(),
    ) {
    }

    /**
     * Runs the pipeline for a single document.
     *
     * @param DocumentEmission $emission The document input.
     * @return EmissionResult The aggregated outcome.
     */
    public function emit(DocumentEmission $emission): EmissionResult
    {
        $generated = $this->xmlMaker->generate($emission->xml);
        $xml = $generated->toString();
        $type = $generated->documentType;
        $accessKey = $generated->accessKey;

        $ownerKey = $emission->ownerKey;
        $date = $emission->storedAt ?? new DateTimeImmutable();
        $store = $this->documentStore !== null && $ownerKey !== null ? $this->documentStore : null;

        $storedPaths = [];

        if ($this->validator !== null)
        {
            $schemaErrors = $this->validator->validate($xml, $type);

            if ($schemaErrors !== [])
            {
                return EmissionResult::schemaFailure($accessKey, $xml, $schemaErrors);
            }
        }

        if ($store !== null)
        {
            $storedPaths['generated.xml'] = $store->putGeneratedXml($ownerKey, $date, $accessKey, $xml);
        }

        $signedXml = null;

        if ($this->signer !== null)
        {
            $signedXml = $this->signer->sign($xml);

            if ($store !== null)
            {
                $storedPaths['signed.xml'] = $store->putSignedXml($ownerKey, $date, $accessKey, $signedXml);
            }
        }

        $send = null;

        if ($this->sender !== null && $signedXml !== null)
        {
            $send = $this->sender->send($accessKey, $signedXml);

            if ($store !== null)
            {
                if ($send->receptionResult !== null)
                {
                    $storedPaths['reception-response.json'] = $store->putReceptionResponse($ownerKey, $date, $accessKey, $send->receptionResult->toArray());
                }

                if ($send->authorizationResult !== null)
                {
                    $storedPaths['authorization-response.json'] = $store->putAuthorizationResponse($ownerKey, $date, $accessKey, $send->authorizationResult->toArray());
                }

                $authorizedXml = $send->authorizationResult?->authorizedDocument?->xml;

                if ($send->success && $authorizedXml !== null && $authorizedXml !== '')
                {
                    $storedPaths['authorized.xml'] = $store->putAuthorizedXml($ownerKey, $date, $accessKey, $authorizedXml);
                }
            }
        }

        $sendSucceeded = $send === null || $send->success;

        $ride = null;

        if ($this->rideGenerator !== null && $emission->ride !== null && $sendSucceeded)
        {
            $ride = $this->rideGenerator->generate($emission->ride);

            if ($store !== null)
            {
                $storedPaths['ride.pdf'] = $store->putRidePdf($ownerKey, $date, $accessKey, $ride->content);
            }
        }

        return new EmissionResult(
            success: $sendSucceeded,
            accessKey: $accessKey,
            generatedXml: $xml,
            signedXml: $signedXml,
            send: $send,
            ride: $ride,
            storedPaths: $storedPaths,
            error: $sendSucceeded ? null : $send->error,
        );
    }
}
