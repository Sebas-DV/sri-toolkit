<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

/**
 * Classification follows the SRI guidelines: rejected vouchers may be resent
 * once the error is corrected, except impediments (closed/inactive RUC or
 * establishment) which must be resolved first, and code 70 which means the
 * voucher is still processing and must not be resent.
 */
enum SriMessageCode: string
{
    case RucNotActive = '2';
    case EstablishmentClosedByAuthority = '10';
    case MaxSizeExceeded = '26';
    case TaxpayerClassNotAllowed = '27';
    case ElectronicMeansAgreementNotAccepted = '28';
    case InvalidDocument = '35';
    case DiscontinuedSchemaVersion = '36';
    case RucWithoutEmissionAuthorization = '37';
    case InvalidSignature = '39';
    case CertificateError = '40';
    case AccessKeyAlreadyRegistered = '43';
    case SequentialAlreadyRegistered = '45';
    case RucDoesNotExist = '46';
    case DocumentTypeDoesNotExist = '47';
    case SchemaDoesNotExist = '48';
    case NullArguments = '49';
    case InternalError = '50';
    case CalculationDifferences = '52';
    case EstablishmentClosed = '56';
    case AuthorizationSuspended = '57';
    case InvalidAccessKeyStructure = '58';
    case RucClosedByAuthority = '63';
    case UntimelyEmissionDate = '65';
    case InvalidDate = '67';
    case AccessKeyInProcessing = '70';
    case InvalidAccessKeyStructureQuery = '80';
    case InvalidTransportStartDate = '82';
    case VatRefundAmountError = '92';

    // Warnings
    case IdentificationNotFound = '59';
    case TestEnvironment = '60';
    case IncorrectIdentification = '62';

    /** @var list<string> Codes that indicate an impediment (must be resolved before resending). */
    private const IMPEDIMENTS = ['2', '10', '27', '37', '46', '56', '57', '63'];

    /** @var list<string> Advisory (warning) codes. */
    private const WARNINGS = ['59', '60', '62'];

    /**
     * Returns a short human-readable description of the message.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this)
        {
            self::RucNotActive => 'Issuer RUC is not active.',
            self::EstablishmentClosedByAuthority => 'Establishment closed by the tax authority.',
            self::MaxSizeExceeded => 'Maximum size exceeded.',
            self::TaxpayerClassNotAllowed => 'Taxpayer class is not allowed to issue electronic documents.',
            self::ElectronicMeansAgreementNotAccepted => 'Electronic means agreement not accepted.',
            self::InvalidDocument => 'Document failed schema validation.',
            self::DiscontinuedSchemaVersion => 'Discontinued schema version.',
            self::RucWithoutEmissionAuthorization => 'RUC without emission authorization.',
            self::InvalidSignature => 'Invalid electronic signature.',
            self::CertificateError => 'Certificate error (not found or not convertible to X509).',
            self::AccessKeyAlreadyRegistered => 'Access key already registered.',
            self::SequentialAlreadyRegistered => 'Sequential already registered.',
            self::RucDoesNotExist => 'Issuer RUC does not exist.',
            self::DocumentTypeDoesNotExist => 'Document type does not exist.',
            self::SchemaDoesNotExist => 'Schema for the document type does not exist.',
            self::NullArguments => 'Null arguments sent to the web service.',
            self::InternalError => 'Unexpected internal server error.',
            self::CalculationDifferences => 'Calculation differences in the document.',
            self::EstablishmentClosed => 'Establishment is closed.',
            self::AuthorizationSuspended => 'Emission authorization is suspended.',
            self::InvalidAccessKeyStructure => 'Access key components differ from the document.',
            self::RucClosedByAuthority => 'Issuer RUC closed by the tax authority.',
            self::UntimelyEmissionDate => 'Emission date is untimely for the emission type.',
            self::InvalidDate => 'Invalid date format.',
            self::AccessKeyInProcessing => 'Access key is still processing.',
            self::InvalidAccessKeyStructureQuery => 'Invalid access key structure in the query.',
            self::InvalidTransportStartDate => 'Transport start date is earlier than the emission date.',
            self::VatRefundAmountError => 'VAT refund amount does not match the DIG authorization.',
            self::IdentificationNotFound => 'Buyer identification not found.',
            self::TestEnvironment => 'Document issued in the test/certification environment.',
            self::IncorrectIdentification => 'Buyer identification is incorrect.',
        };
    }

    /**
     * Whether the message is an advisory warning rather than a rejection.
     */
    public function isWarning(): bool
    {
        return in_array($this->value, self::WARNINGS, true);
    }

    /**
     * Whether the voucher is still processing (do not resend; wait).
     */
    public function isProcessing(): bool
    {
        return $this === self::AccessKeyInProcessing;
    }

    /**
     * Whether the error is an impediment that must be resolved before resending.
     */
    public function isImpediment(): bool
    {
        return in_array($this->value, self::IMPEDIMENTS, true);
    }

    /**
     * Whether the voucher can be corrected and resent with the same access key.
     */
    public function isRetryable(): bool
    {
        return ! $this->isWarning() && ! $this->isProcessing() && ! $this->isImpediment();
    }
}
