<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Enums\ErrorContext;
use App\Services\SiaIpDc09\Exceptions\InvalidFrameException;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

// If using lorisleiva/laravel-actions:
// use Lorisleiva\Actions\Concerns\AsAction;

class ExtractMessageFrame
{
    // If using lorisleiva/laravel-actions:
    use AsAction;

    private const HEX_LF = '0a'; // Line Feed

    private const HEX_CR = '0d'; // Carriage Return

    /**
     * Extracts the core SIA message frame (CRC+Length+Body) from a hexadecimal string representation.
     * The input hex string is expected to be wrapped with 0a (LF) and 0d (CR).
     * This method extracts only the first complete frame found.
     *
     * @param  string  $rawMessageHex  The hexadecimal string representation of the received data.
     * @param  string  $remoteIp  The source IP address for logging/error context.
     * @return string The extracted message frame content (CRC+Length+Body) as a raw binary string.
     *
     * @throws InvalidFrameException If delimiters are missing, misplaced, or content is not valid hex.
     */
    public function handle(string $rawMessageHex, string $remoteIp): string
    {

        // Ensure the input is lowercase for consistent searching and processing
        $processedHex = strtolower(trim($rawMessageHex)); // Trim whitespace just in case

        // 1. Check for starting Line Feed (0a)
        if (! str_starts_with($processedHex, self::HEX_LF)) {
            $errorMessage = 'Invalid frame: Missing starting Line Feed (0a) delimiter.';
            Log::warning($errorMessage, ['ip' => $remoteIp, 'raw_hex_preview' => substr($processedHex, 0, 60)]);
            throw new InvalidFrameException(
                message: $errorMessage,
                fullRawFrame: $rawMessageHex, // Pass original for context
                extractedMessageBody: null,
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: 0 // Error is at the very beginning
            );
        }

        // 2. Find the first Carriage Return (0d)
        // We search *after* the initial LF
        $crPos = strpos($processedHex, self::HEX_CR, strlen(self::HEX_LF));

        if ($crPos === false) {
            $errorMessage = 'Invalid frame: Missing trailing Carriage Return (0d) delimiter.';
            Log::warning($errorMessage, ['ip' => $remoteIp, 'raw_hex_preview' => substr($processedHex, 0, 60)]);
            throw new InvalidFrameException(
                message: $errorMessage,
                fullRawFrame: $rawMessageHex,
                extractedMessageBody: null,
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: strlen($processedHex) // Error is effectively at the end
            );
        }

        // 3. Extract the hexadecimal content between LF and CR
        $contentStartPos = strlen(self::HEX_LF);
        $contentLength = $crPos - $contentStartPos;

        if ($contentLength <= 0) {
            $errorMessage = 'Invalid frame: No content found between LF (0a) and CR (0d) delimiters.';
            Log::warning($errorMessage, ['ip' => $remoteIp, 'raw_hex_preview' => substr($processedHex, 0, 60)]);
            throw new InvalidFrameException(
                message: $errorMessage,
                fullRawFrame: $rawMessageHex,
                extractedMessageBody: null,
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: $contentStartPos // Error is after LF
            );
        }

        $contentHex = substr($processedHex, $contentStartPos, $contentLength);

        // 4. Convert the extracted hex content to binary
        // Suppress errors for hex2bin and check its return value manually for better control
        $binaryContent = @hex2bin($contentHex);

        if ($binaryContent === false) {
            $errorMessage = 'Invalid frame: Content between delimiters is not valid hexadecimal.';
            Log::warning($errorMessage, ['ip' => $remoteIp, 'invalid_hex_content' => $contentHex]);
            throw new InvalidFrameException(
                message: $errorMessage,
                fullRawFrame: $rawMessageHex,
                extractedMessageBody: null, // Cannot provide body as it's invalid hex
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: $contentStartPos // Error relates to the content part
            );
        }

        // Log::debug("Successfully extracted binary frame content.", ['ip' => $remoteIp, 'binary_length' => strlen($binaryContent)]);
        return $binaryContent; // This is CRC+LengthHeader+SiaBody
    }
}
