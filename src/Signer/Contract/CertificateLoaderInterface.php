<?php


declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

use MTZ\Toolkit\Signer\Data\CertificateData;

interface CertificateLoaderInterface
{
    public function load(string $certificatePath, string $certificatePassword): CertificateData;
}
