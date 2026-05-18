<?php


declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Exceptions\CertificateException;

/**
 * Contract for loading certificate and private key data from a certificate file.
 */
interface CertificateLoaderInterface
{
    /**
     * Load certificate data from a PKCS#12 file.
     *
     * @param string $certificatePath The filesystem path to the certificate file.
     * @param string $certificatePassword The password protecting the certificate file.
     * @return CertificateData Parsed certificate and private key data.
     * @throws CertificateException When the certificate cannot be loaded or parsed.
     */
    public function load(string $certificatePath, string $certificatePassword): CertificateData;
}
