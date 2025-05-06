<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Services\SiaIpDc09\Enums\ErrorContext;
use Throwable;

class CrcMismatchException extends SiaMessageException
{
    protected string $expectedCrc;

    protected string $calculatedCrc;

    public function __construct(
        string $message,
        string $expectedCrc,
        string $calculatedCrc,
        string $fullRawFrame,
        string $extractedMessageBody, // Body is always available for CRC check
        // ErrorContext is always FRAME_VALIDATION for OLLL/CRC
        // Offset is always 0 for CRC header
        // ParsedHeaderParts is always null for CRC error
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $fullRawFrame,
            $extractedMessageBody,
            ErrorContext::FRAME_VALIDATION, // CRC validation is part of frame validation
            0, // CRC is at the start of the frame (offset 0 relative to frame content)
            null, // No header parts would be parsed if CRC fails
            $code,
            $previous
        );
        $this->expectedCrc = $expectedCrc;
        $this->calculatedCrc = $calculatedCrc;

        // Add specific details to context
        $this->exceptionContextData['expected_crc'] = $this->expectedCrc;
        $this->exceptionContextData['calculated_crc'] = $this->calculatedCrc;
    }

    public function getExpectedCrc(): string
    {
        return $this->expectedCrc;
    }

    public function getCalculatedCrc(): string
    {
        return $this->calculatedCrc;
    }
}
