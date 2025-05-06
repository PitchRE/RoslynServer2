<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Enums;

enum ErrorContext: string
{
    /** Error during CRC/Length/basic structure check of the full binary frame (CRC+Length+Body). */
    case FRAME_VALIDATION = 'frame_validation';

    /** Error during parsing of the message body (ID, seq, account, data block, timestamp, etc.). */
    case BODY_PARSING = 'body_parsing';

    /** Error context is unknown or could not be determined. */
    case UNKNOWN = 'unknown';
}
