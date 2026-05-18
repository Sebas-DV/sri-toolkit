<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Exceptions;

use RuntimeException;

/**
 * Base exception class for all SRI sender-related errors.
 *
 * Extends RuntimeException so that callers can catch any sender-level
 * exception without worrying about specific subtypes.
 */
class SenderException extends RuntimeException
{
}
