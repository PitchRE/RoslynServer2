<?php

declare(strict_types=1);

namespace App\Actions\SiaIpDc09;

use App\Services\SiaIpDc09\Contracts\DecryptionService;
use App\Services\SiaIpDc09\Data\ParsedContentDto;
use App\Services\SiaIpDc09\Data\ParsedHeaderDto;
use App\Services\SiaIpDc09\Enums\ErrorContext;
use App\Services\SiaIpDc09\Exceptions\DecryptionErrorException;
use App\Services\SiaIpDc09\Exceptions\GenericParsingException;
use App\Services\SiaIpDc09\Exceptions\TimestampInvalidException;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException as CarbonInvalidFormatException; // For decryption failures
use Illuminate\Support\Facades\Log; // For mandatory timestamp on encrypted

class ProcessSiaDataContent
{
    private const SIA_TIMESTAMP_PREFIX = '_';

    private const SIA_TIMESTAMP_EXPECTED_LENGTH = 20;

    private const PAD_SEPARATOR = '|';

    private DecryptionService $decryptionService;

    public function __construct(DecryptionService $decryptionService)
    {
        $this->decryptionService = $decryptionService;
    }

    /**
     * Processes the SIA message content block. This involves:
     * - Decrypting the content if it was encrypted.
     * - Extracting padding if decryption occurred.
     * - Parsing the main data block `[...]`, optional extended data `[X...]`,
     *   and the optional (but mandatory for encrypted) SIA timestamp `_...`.
     *
     * @param  ParsedHeaderDto  $parsedHeaderDto  DTO containing parsed header info and the relevant content block.
     * @return ParsedContentDto DTO containing the parsed message data, extended data, and timestamp.
     *
     * @throws GenericParsingException If the content structure is malformed.
     * @throws DecryptionErrorException If decryption fails.
     * @throws TimestampInvalidException If a mandatory timestamp for an encrypted message is missing/invalid.
     */
    public function handle(ParsedHeaderDto $parsedHeaderDto): ParsedContentDto
    {
        $contentToProcess = $parsedHeaderDto->remainingBodyContent;
        $headerForExceptionContext = (array) $parsedHeaderDto; // For exception context
        $finalContentToParse = $parsedHeaderDto->remainingBodyContent;

        if ($parsedHeaderDto->wasEncrypted) {
            // Content is expected to be "[HEX_ENCRYPTED_DATA]"
            if (! str_starts_with($contentToProcess, '[') || ! str_ends_with($contentToProcess, ']')) {
                throw new GenericParsingException(
                    message: 'Encrypted content block missing surrounding brackets "[...]".',
                    fullRawFrame: $parsedHeaderDto->rawFrame,
                    extractedMessageBody: $parsedHeaderDto->rawBody,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: strlen($parsedHeaderDto->rawBody) - strlen($contentToProcess),
                    parsedHeaderParts: $headerForExceptionContext
                );
            }

            $hexEncryptedData = substr($contentToProcess, 1, -1); // Remove brackets
            if (empty($hexEncryptedData)) {
                throw new GenericParsingException( // Or DecryptionErrorException if preferred for empty encrypted block
                    message: 'Encrypted data block inner content is empty after removing brackets.',
                    fullRawFrame: $parsedHeaderDto->rawFrame,
                    extractedMessageBody: $parsedHeaderDto->rawBody,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: (strlen($parsedHeaderDto->rawBody) - strlen($contentToProcess)) + 1,
                    parsedHeaderParts: $headerForExceptionContext
                );
            }

            // Basic hex validation before attempting decryption
            if (! ctype_xdigit($hexEncryptedData)) {
                throw new GenericParsingException(
                    message: 'Encrypted data content contains non-hexadecimal characters.',
                    fullRawFrame: $parsedHeaderDto->rawFrame,
                    extractedMessageBody: $parsedHeaderDto->rawBody,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: (strlen($parsedHeaderDto->rawBody) - strlen($contentToProcess)) + 1,
                    parsedHeaderParts: $headerForExceptionContext
                );
            }

            $decryptedPlaintext = $this->decryptionService->handle(
                encryptedHexData: $hexEncryptedData,
                panelAccountNumber: $parsedHeaderDto->panelAccountNumber,
                receiverNumber: $parsedHeaderDto->receiverNumber,
                linePrefix: $parsedHeaderDto->linePrefix
            );

            if ($decryptedPlaintext === null) {
                // DecryptionService should ideally throw its own specific exception,
                // but if it returns null, we wrap it here.
                throw new DecryptionErrorException(
                    message: 'Decryption failed or returned null.',
                    fullRawFrame: $parsedHeaderDto->rawFrame,
                    extractedMessageBody: $parsedHeaderDto->rawBody,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: (strlen($parsedHeaderDto->rawBody) - strlen($contentToProcess)) + 1, // Offset of encrypted block
                    parsedHeaderParts: $headerForExceptionContext
                );
            }
            // Log::debug("Decryption successful for account {$parsedHeaderDto->panelAccountNumber}.");

            // After decryption, plaintext is "PaddingBytes|ActualDataAndTimestamp"
            $padSeparatorPos = strpos($decryptedPlaintext, self::PAD_SEPARATOR);
            if ($padSeparatorPos === false) {
                throw new GenericParsingException( // This indicates a severe decryption or padding error
                    message: 'Decrypted data block missing mandatory pad separator "|". Possible decryption or padding scheme error.',
                    fullRawFrame: $parsedHeaderDto->rawFrame,
                    extractedMessageBody: $parsedHeaderDto->rawBody, // Body here is the *original encrypted* body
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: (strlen($parsedHeaderDto->rawBody) - strlen($contentToProcess)) + 1,
                    parsedHeaderParts: $headerForExceptionContext + ['decrypted_preview' => substr($decryptedPlaintext, 0, 50)]
                );
            }
            // $padding = substr($decryptedPlaintext, 0, $padSeparatorPos); // For logging if needed
            $finalContentToParse = substr($decryptedPlaintext, $padSeparatorPos + 1);
            // Now $contentToParse holds "ActualData[...]OptionalExtData[...]OptionalTimestamp"
        }

        // --- Common parsing logic for plaintext (either original or decrypted) ---
        $parsedResult = $this->parsePlaintextSiaDataContent(
            contentToParse: $finalContentToParse,
            fullRawFrameForContext: $parsedHeaderDto->rawFrame,
            fullBodyForContext: $parsedHeaderDto->rawBody, // This is the *original* body
            offsetOfThisContentInFullBody: strlen($parsedHeaderDto->rawBody) - strlen($parsedHeaderDto->remainingBodyContent), // Initial offset
            headerForExceptionContext: $headerForExceptionContext,
            panelAccountNumberForLogging: $parsedHeaderDto->panelAccountNumber
        );

        // SIA Spec 5.5.1.9: "The timestamp shall be included in encrypted messages"
        // SIA Spec 5.4.3: Encrypted elements include data, timestamp, and padding.
        if ($parsedHeaderDto->wasEncrypted && $parsedResult->siaTimestamp === null) {
            $correctiveNakTimestamp = CarbonImmutable::now('UTC')->format('\_H:i:s,m-d-Y');
            throw new TimestampInvalidException(
                message: 'Encrypted message is missing the mandatory SIA Timestamp after parsing content.',
                correctiveNakTimestamp: $correctiveNakTimestamp,
                fullRawFrame: $parsedHeaderDto->rawFrame,
                extractedMessageBody: $parsedHeaderDto->rawBody,
                parsedHeaderParts: $headerForExceptionContext,
                offsetWithinContext: strlen($parsedHeaderDto->rawBody) // Error is effectively "at the end" if timestamp is missing
            );
        }

        return $parsedResult;
    }

    /**
     * Private helper to parse the actual plaintext content (message data, extended data, timestamp).
     * This is called with either originally unencrypted content or decrypted content.
     */
    private function parsePlaintextSiaDataContent(
        string $contentToParse,
        string $fullRawFrameForContext,
        string $fullBodyForContext,
        int $offsetOfThisContentInFullBody, // Offset where $contentToParse starts within $fullBodyForContext
        array $headerForExceptionContext,
        string $panelAccountNumberForLogging
    ): ParsedContentDto {
        $messageData = '';
        $extendedData = [];
        $rawSiaTimestamp = null;
        $parsedSiaTimestamp = null;

        $currentPos = 0;
        $contentLength = strlen($contentToParse);

        // 1. Parse Main Data Block: [...]
        if ($currentPos < $contentLength && $contentToParse[$currentPos] === '[') {
            $mainDataBlockEndPos = strpos($contentToParse, ']', $currentPos + 1);
            if ($mainDataBlockEndPos === false) {
                throw new GenericParsingException(
                    message: 'Plaintext content parsing error: Missing closing "]" for main data block.',
                    fullRawFrame: $fullRawFrameForContext,
                    extractedMessageBody: $fullBodyForContext,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                    parsedHeaderParts: $headerForExceptionContext
                );
            }
            $messageData = substr($contentToParse, $currentPos + 1, $mainDataBlockEndPos - ($currentPos + 1));
            $currentPos = $mainDataBlockEndPos + 1;
        }

        // 2. Parse Optional Extended Data Blocks or Timestamp
        while ($currentPos < $contentLength) {
            $char = $contentToParse[$currentPos];
            $remainingLengthForTimestamp = $contentLength - $currentPos;

            if ($char === '[') { // Potential Extended Data
                if (($currentPos + 2) >= $contentLength) {
                    Log::debug("Found '[' but not enough characters for a valid extended data block before end of content.", ['offset' => $offsetOfThisContentInFullBody + $currentPos, 'account' => $panelAccountNumberForLogging]);
                    break;
                }
                $identifier = $contentToParse[$currentPos + 1];
                $extDataBlockEndPos = strpos($contentToParse, ']', $currentPos + 2);

                if ($extDataBlockEndPos === false) {
                    throw new GenericParsingException( /* ... as before ... */
                        message: "Plaintext content parsing error: Missing closing ']' for an extended data block.",
                        fullRawFrame: $fullRawFrameForContext,
                        extractedMessageBody: $fullBodyForContext,
                        errorContext: ErrorContext::BODY_PARSING,
                        offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                        parsedHeaderParts: $headerForExceptionContext
                    );
                }
                if (! ctype_upper($identifier)) {
                    Log::debug('Potential extended data block did not start with an uppercase identifier. Stopping extended data parse.', ['char' => $identifier, 'offset' => $offsetOfThisContentInFullBody + $currentPos + 1, 'account' => $panelAccountNumberForLogging]);
                    break;
                }
                $data = substr($contentToParse, $currentPos + 2, $extDataBlockEndPos - ($currentPos + 2));
                $extendedData[$identifier] = $data;
                $currentPos = $extDataBlockEndPos + 1;

            } elseif ($char === self::SIA_TIMESTAMP_PREFIX) { // Potential Timestamp
                if ($remainingLengthForTimestamp === self::SIA_TIMESTAMP_EXPECTED_LENGTH) {
                    $rawSiaTimestamp = substr($contentToParse, $currentPos, self::SIA_TIMESTAMP_EXPECTED_LENGTH);
                    $tsStringForParsing = substr($rawSiaTimestamp, 1);
                    $pattern = '/^(\d{2}):(\d{2}):(\d{2}),(\d{2})-(\d{2})-(\d{4})$/';
                    if (preg_match($pattern, $tsStringForParsing, $matches)) {
                        try {
                            $parsedSiaTimestamp = CarbonImmutable::create(
                                year: (int) $matches[6],
                                month: (int) $matches[4],
                                day: (int) $matches[5],
                                hour: (int) $matches[1],
                                minute: (int) $matches[2],
                                second: (int) $matches[3],
                                timezone: 'UTC'
                            );
                            if ($parsedSiaTimestamp->format('H:i:s,m-d-Y') !== $tsStringForParsing) {
                                throw new GenericParsingException( /* ... as before ... */
                                    message: "Plaintext content parsing error: SIA Timestamp '{$rawSiaTimestamp}' has invalid date/time components.",
                                    fullRawFrame: $fullRawFrameForContext,
                                    extractedMessageBody: $fullBodyForContext,
                                    errorContext: ErrorContext::BODY_PARSING,
                                    offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                                    parsedHeaderParts: $headerForExceptionContext
                                );
                            }
                        } catch (CarbonInvalidFormatException $e) {
                            throw new GenericParsingException( /* ... as before ... */
                                message: "Plaintext content parsing error: SIA Timestamp '{$rawSiaTimestamp}' parse failed: ".$e->getMessage(),
                                fullRawFrame: $fullRawFrameForContext,
                                extractedMessageBody: $fullBodyForContext,
                                errorContext: ErrorContext::BODY_PARSING,
                                offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                                parsedHeaderParts: $headerForExceptionContext,
                                previous: $e
                            );
                        }
                    } else {
                        throw new GenericParsingException( /* ... as before ... */
                            message: "Plaintext content parsing error: SIA Timestamp '{$rawSiaTimestamp}' format invalid.",
                            fullRawFrame: $fullRawFrameForContext,
                            extractedMessageBody: $fullBodyForContext,
                            errorContext: ErrorContext::BODY_PARSING,
                            offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                            parsedHeaderParts: $headerForExceptionContext
                        );
                    }
                    $currentPos += self::SIA_TIMESTAMP_EXPECTED_LENGTH;
                    break; // Timestamp MUST be the last element
                } else {
                    throw new GenericParsingException( /* ... as before ... */
                        message: 'Plaintext content parsing error: Potential SIA Timestamp found but has incorrect remaining length.',
                        fullRawFrame: $fullRawFrameForContext,
                        extractedMessageBody: $fullBodyForContext,
                        errorContext: ErrorContext::BODY_PARSING,
                        offsetWithinContext: $offsetOfThisContentInFullBody + $currentPos,
                        parsedHeaderParts: $headerForExceptionContext
                    );
                }
            } else {
                break; // Unexpected character
            }
        }

        // Check for any trailing unexpected data
        if ($currentPos < $contentLength) {
            $trailingData = substr($contentToParse, $currentPos);
            if (trim($trailingData) !== '') {
                Log::warning('Unexpected trailing data found after parsing plaintext content.', [
                    'trailing_data_hex' => bin2hex($trailingData),
                    'account' => $panelAccountNumberForLogging,
                ]);
                // Optionally throw an exception for strictness
            }
        }

        return new ParsedContentDto(
            messageData: $messageData,
            extendedData: $extendedData,
            rawSiaTimestamp: $rawSiaTimestamp,
            siaTimestamp: $parsedSiaTimestamp
        );
    }
}
