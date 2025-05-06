<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

// Decryption errors happen when processing the body content.
class DecryptionErrorException extends SiaMessageException
{
    // Inherits constructor. Calling code will provide ErrorContext::BODY_PARSING,
    // offset where encrypted block starts, and parsed header.
}
