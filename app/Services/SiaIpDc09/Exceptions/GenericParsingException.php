<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

// This exception is for errors found *after* frame validation (CRC/Length)
// So, errorContext will typically be BODY_PARSING.
class GenericParsingException extends SiaMessageException
{
    // Inherits constructor from SiaMessageException.
    // Calling code will pass ErrorContext::BODY_PARSING, the offset within the body,
    // and any header parts parsed up to the point of error.
}
