<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Enums;

enum ResponseType: string
{
    /** Positive Acknowledgment (message received and processed successfully). */
    case ACK = 'ACK';

    /** Negative Acknowledgment (typically for timestamp errors on encrypted messages, or general issues). */
    case NAK = 'NAK';

    /**
     * Receiver Unable to Handle (message received but could not be processed correctly,
     * e.g., format error post-CRC/Length, unsupported element, unknown token).
     */
    case DUH = 'DUH';

    /**
     * No response should be sent by this system.
     * (e.g., CRC error, decryption error, fundamental frame structure error before SIA parsing).
     */
    case NONE = 'NONE';

    // If you implement Remote Commands (Annex I) in the future:
    // case RSP = 'RSP';
}
