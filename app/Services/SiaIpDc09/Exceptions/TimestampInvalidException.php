<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Services\SiaIpDc09\Enums\ErrorContext;
use Throwable;

class TimestampInvalidException extends SiaMessageException
{
    protected string $correctiveNakTimestamp; // The timestamp CSR should send in NAK

    /**
     * @param  string  $correctiveNakTimestamp  CSR's current GMT timestamp formatted for NAK response (_HH:MM:SS,MM-DD-YYYY).
     * @param  array<string, mixed>|null  $parsedHeaderParts  Key header fields parsed before the error.
     * @param  int|null  $offsetWithinContext  Offset where the problematic timestamp (or lack thereof) was noted.
     */
    public function __construct(
        string $message,
        string $correctiveNakTimestamp,
        string $fullRawFrame,
        ?string $extractedMessageBody, // Body would be available
        ?array $parsedHeaderParts,     // Header would be parsed
        ?int $offsetWithinContext,      // Usually at the end of the body or where timestamp was expected
        int $code = 0,
        ?Throwable $previous = null
    ) {
        // Timestamp issues are found when parsing the body content.
        parent::__construct(
            $message,
            $fullRawFrame,
            $extractedMessageBody,
            ErrorContext::BODY_PARSING,
            $offsetWithinContext,
            $parsedHeaderParts,
            $code,
            $previous
        );
        $this->correctiveNakTimestamp = $correctiveNakTimestamp;
        $this->exceptionContextData['corrective_nak_timestamp'] = $this->correctiveNakTimestamp;
    }

    public function getCorrectiveNakTimestamp(): string
    {
        return $this->correctiveNakTimestamp;
    }
}
