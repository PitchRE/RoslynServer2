<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Data;

use Carbon\CarbonImmutable;

readonly class FinalParsedSiaDataDto
{
    /**
     * @param  string  $rawFrame  The complete raw binary frame (CRC+Length+Body).
     * @param  string  $rawBody  The binary message body.
     * @param  string  $crcHeader  The 4-character hexadecimal CRC from the frame header.
     * @param  int  $lengthHeaderValue  The integer value of the body length from the '0LLL' header.
     * @param  string  $protocolToken  The protocol identifier (e.g., "ADM-CID").
     * @param  bool  $wasEncrypted  True if the message was originally encrypted.
     * @param  string  $sequenceNumber  The 4-digit sequence number.
     * @param  string|null  $receiverNumber  The receiver number, if present.
     * @param  string  $linePrefix  The line/account prefix.
     * @param  string  $panelAccountNumber  The panel's account number from the #acct field. // RENAMED
     * @param  string  $messageData  The primary message data content from `[...]`.
     * @param  array<string, string>  $extendedData  Associative array of extended data.
     * @param  string|null  $rawSiaTimestamp  The raw SIA timestamp string from the message.
     * @param  CarbonImmutable|null  $siaTimestamp  The parsed CarbonImmutable SIA timestamp object (UTC).
     * @param  string|null  $remoteIp  The remote IP address from which the message was received.
     * @param  int|null  $remotePort  The remote port from which the message was received.
     */
    public function __construct(
        // Frame Info from ValidatedFrameDto
        public string $rawFrame,
        public string $rawBody,
        public string $crcHeader,
        public int $lengthHeaderValue,

        // Header Info from ParsedHeaderDto
        public string $protocolToken,
        public bool $wasEncrypted,
        public string $sequenceNumber,
        public ?string $receiverNumber,
        public string $linePrefix,
        public string $panelAccountNumber, // RENAMED

        // Content Info from ParsedContentDto
        public string $messageData,
        public array $extendedData,
        public ?string $rawSiaTimestamp,
        public ?CarbonImmutable $siaTimestamp,

        // Contextual Info (e.g., from listener)
        public ?string $remoteIp,
        public ?int $remotePort,
    ) {}
}
