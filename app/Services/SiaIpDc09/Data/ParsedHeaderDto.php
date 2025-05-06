<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Data;

readonly class ParsedHeaderDto
{
    /**
     * @param  string  $rawFrame  The complete raw binary frame received.
     * @param  string  $rawBody  The full binary message body.
     * @param  string  $protocolToken  The protocol identifier string (e.g., "ADM-CID", "SIA-DCS"), without the '*'.
     * @param  bool  $wasEncrypted  Indicates if the original token had a '*' prefix.
     * @param  string  $sequenceNumber  The 4-digit sequence number.
     * @param  string|null  $receiverNumber  The receiver number (1-6 hex digits), if present.
     * @param  string  $linePrefix  The line/account prefix (1-6 hex digits, e.g., "0" for L0).
     * @param  string  $panelAccountNumber  The SIA protocol account number (3-16 hex digits). // RENAMED
     * @param  string  $remainingBodyContent  The rest of the body content after the account number.
     */
    public function __construct(
        public string $rawFrame,
        public string $rawBody,
        public string $protocolToken,
        public bool $wasEncrypted,
        public string $sequenceNumber,
        public ?string $receiverNumber,
        public string $linePrefix,
        public string $panelAccountNumber, // RENAMED
        public string $remainingBodyContent,
    ) {}
}
