<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Documents\Enums;

enum DocumentArtifact: string
{
    case GeneratedXml = 'generated.xml';
    case SignedXml = 'signed.xml';
    case AuthorizedXml = 'authorized.xml';
    case ReceptionResponse = 'reception-response.json';
    case AuthorizationResponse = 'authorization-response.json';
    case RidePdf = 'ride.pdf';
}
