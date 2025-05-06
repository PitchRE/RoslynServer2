<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Services\SiaIpDc09\Enums\ErrorContext;
use Throwable;

class UnsupportedElementException extends SiaMessageException
{
    protected string $unsupportedElementDescription;

    public function __construct(
        string $message,
        string $unsupportedElementDescription,
        string $fullRawFrame,
        string $extractedMessageBody, // Body would be available
        array $parsedHeaderParts,     // Header would be parsed
        int $offsetWithinContext,      // Offset of the unsupported element within the body
        int $code = 0,
        ?Throwable $previous = null
    ) {
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
        $this->unsupportedElementDescription = $unsupportedElementDescription;
        $this->exceptionContextData['unsupported_element_description'] = $this->unsupportedElementDescription;
    }

    public function getUnsupportedElementDescription(): string
    {
        return $this->unsupportedElementDescription;
    }
}
