<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Services\SiaIpDc09\Enums\ErrorContext;
use RuntimeException; // Or extend \Exception
use Throwable;

/**
 * Abstract base exception for all errors encountered during the direct parsing
 * and structural validation of a SIA DC-09 message frame.
 *
 * This class captures detailed context about the raw message and where
 * the parsing failed.
 */
abstract class SiaMessageException extends RuntimeException
{
    protected string $fullRawFrame;

    protected ?string $extractedMessageBody; // The body part (after CRC/Length headers)

    protected ErrorContext $errorContext;

    protected ?int $offsetWithinContext;

    protected ?array $parsedHeaderParts; // Key header fields parsed before this specific error

    /**
     * @var array<string, mixed> Holds structured context for logging.
     */
    protected array $exceptionContextData = [];

    /**
     * @param  string  $message  The primary exception message.
     * @param  string  $fullRawFrame  The complete raw binary message frame received.
     * @param  string|null  $extractedMessageBody  The extracted binary message body (if available before error).
     * @param  ErrorContext  $errorContext  The context (frame or body) where the error occurred.
     * @param  int|null  $offsetWithinContext  The character offset within the specified context (frame or body) where the error was detected.
     * @param  array<string, mixed>|null  $parsedHeaderParts  Key header fields parsed before the error.
     * @param  int  $code  The exception code.
     * @param  Throwable|null  $previous  The previous throwable used for the exception chain.
     */
    public function __construct(
        string $message,
        string $fullRawFrame,
        ?string $extractedMessageBody,
        ErrorContext $errorContext,
        ?int $offsetWithinContext = null,
        ?array $parsedHeaderParts = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->fullRawFrame = $fullRawFrame;
        $this->extractedMessageBody = $extractedMessageBody;
        $this->errorContext = $errorContext;
        $this->offsetWithinContext = $offsetWithinContext;
        $this->parsedHeaderParts = $parsedHeaderParts;

        $this->buildExceptionContext();
    }

    /**
     * Builds the common context array for logging purposes.
     * Concrete exception classes can add more specific details.
     */
    protected function buildExceptionContext(): void
    {
        $this->exceptionContextData = [
            'error_message' => $this->getMessage(),
            'exception_class' => static::class,
            'error_context_type' => $this->errorContext->value,
            'offset_in_context' => $this->offsetWithinContext,
            'parsed_header_before_error' => $this->parsedHeaderParts,
            // Provide previews in hex for non-printable binary data
            'full_frame_hex_preview' => substr(bin2hex($this->fullRawFrame), 0, 160).(strlen($this->fullRawFrame) > 80 ? '...' : ''),
        ];

        $contextualDataForExcerpt = $this->errorContext === ErrorContext::FRAME_VALIDATION
            ? $this->fullRawFrame
            : $this->extractedMessageBody;

        if ($contextualDataForExcerpt !== null && $this->offsetWithinContext !== null) {
            $this->exceptionContextData['excerpt_at_offset_hex'] = substr(
                bin2hex($contextualDataForExcerpt),
                max(0, ($this->offsetWithinContext * 2) - 20), // *2 for hex, -20 for 10 bytes before
                50 // 25 bytes hex representation
            ).'...';
        }

        if ($this->extractedMessageBody !== null) {
            $this->exceptionContextData['message_body_hex_preview'] = substr(bin2hex($this->extractedMessageBody), 0, 160).(strlen($this->extractedMessageBody) > 80 ? '...' : '');
        } else {
            $this->exceptionContextData['message_body_hex_preview'] = '[Not Available or Not Yet Parsed]';
        }

        if ($this->getPrevious()) {
            $this->exceptionContextData['previous_exception_class'] = get_class($this->getPrevious());
            $this->exceptionContextData['previous_exception_message'] = $this->getPrevious()->getMessage();
        }

        $this->exceptionContextData = array_filter($this->exceptionContextData); // Remove nulls
    }

    public function getFullRawFrame(): string
    {
        return $this->fullRawFrame;
    }

    public function getExtractedMessageBody(): ?string
    {
        return $this->extractedMessageBody;
    }

    public function getErrorContextType(): ErrorContext
    {
        return $this->errorContext;
    }

    public function getOffsetWithinContext(): ?int
    {
        return $this->offsetWithinContext;
    }

    public function getParsedHeaderParts(): ?array
    {
        return $this->parsedHeaderParts;
    }

    /**
     * Returns the structured context data array, suitable for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->exceptionContextData;
    }

    /**
     * Allows adding more context externally (e.g., remote_ip).
     *
     * @param  array<string, mixed>  $context
     */
    public function addContext(array $context): self
    {
        $this->exceptionContextData = array_merge($this->exceptionContextData, $context);

        return $this;
    }
}
