<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Data\ValidatedFrameDto; // Import the contract
use App\Services\SiaIpDc09\Enums\ErrorContext;
use App\Services\SiaIpDc09\Exceptions\CrcMismatchException;
use App\Services\SiaIpDc09\Exceptions\GenericParsingException;
use App\Support\Crc\Contracts\CrcCalculator as CrcCalculatorContract;
use Illuminate\Support\Facades\Log;

// If using lorisleiva/laravel-actions:
// use Lorisleiva\Actions\Concerns\AsAction;

class ValidateSiaFrame
{
    // If using lorisleiva/laravel-actions:
    // use AsAction;

    private const LENGTH_HEADER_START_CHAR = '0';

    private const LENGTH_HEADER_TOTAL_LENGTH = 4; // "0LLL"

    private const MIN_FRAME_CONTENT_LENGTH_AFTER_HEADERS = 1; // Must have at least some body

    private CrcCalculatorContract $crcCalculator;

    /**
     * Constructor with dependency injection for the CRC calculator.
     */
    public function __construct(CrcCalculatorContract $crcCalculator)
    {
        $this->crcCalculator = $crcCalculator;
    }

    /**
     * Validates the SIA frame's CRC and Length header.
     *
     * @param  string  $binaryFrameContent  The raw binary string representing (CRC_header + Length_header + SIA_body).
     *                                      This is the output from ExtractMessageFrame.
     * @return ValidatedFrameDto DTO containing the validated raw body and header info.
     *
     * @throws CrcMismatchException If CRC validation fails.
     * @throws GenericParsingException If length header format is invalid or body length mismatch.
     */
    public function handle(string $binaryFrameContent): ValidatedFrameDto
    {
        $fullFrameContentLength = strlen($binaryFrameContent);

        // 1. Get expected CRC header length from the injected calculator
        $crcHeaderLength = $this->crcCalculator->getHexStringLength(); // Typically 4 for CRC-16

        // 2. Basic length check: Must be long enough for CRC header, Length header, and some body
        $minExpectedTotalLength = $crcHeaderLength + self::LENGTH_HEADER_TOTAL_LENGTH + self::MIN_FRAME_CONTENT_LENGTH_AFTER_HEADERS;
        if ($fullFrameContentLength < $minExpectedTotalLength) {
            throw new GenericParsingException(
                message: "Frame content is too short. Expected at least {$minExpectedTotalLength} bytes for CRC, Length, and Body, got {$fullFrameContentLength}.",
                fullRawFrame: $binaryFrameContent, // In this context, binaryFrameContent is the "full frame" being validated
                extractedMessageBody: null,
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: 0
            );
        }

        // 3. Extract CRC Header from the binary frame content
        $crcHeaderFromFrame = substr($binaryFrameContent, 0, $crcHeaderLength);

        // 4. Extract and Validate Length Header ("0LLL")
        $lengthHeaderString = substr($binaryFrameContent, $crcHeaderLength, self::LENGTH_HEADER_TOTAL_LENGTH);

        if (
            strlen($lengthHeaderString) !== self::LENGTH_HEADER_TOTAL_LENGTH ||
            $lengthHeaderString[0] !== self::LENGTH_HEADER_START_CHAR ||
            ! ctype_xdigit(substr($lengthHeaderString, 1)) // Check if LLL part is hex
        ) {
            $actualLengthHeaderPreview = substr($binaryFrameContent, $crcHeaderLength, self::LENGTH_HEADER_TOTAL_LENGTH);
            throw new GenericParsingException(
                message: "Invalid Length Header format. Expected '0LLL' (L=hex), got '{$actualLengthHeaderPreview}'.",
                fullRawFrame: $binaryFrameContent,
                extractedMessageBody: null,
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: $crcHeaderLength // Error is at the start of the length header
            );
        }

        $declaredBodyLength = hexdec(substr($lengthHeaderString, 1)); // Get integer value of LLL

        // 5. Extract the actual SIA Body
        $headersTotalLength = $crcHeaderLength + self::LENGTH_HEADER_TOTAL_LENGTH;
        $actualSiaBody = substr($binaryFrameContent, $headersTotalLength);
        $actualSiaBodyLength = strlen($actualSiaBody);

        // 6. Compare Declared Body Length with Actual Body Length
        if ($declaredBodyLength !== $actualSiaBodyLength) {
            throw new GenericParsingException(
                message: "Body length mismatch. Header '{$lengthHeaderString}' declared {$declaredBodyLength} bytes, but actual body length is {$actualSiaBodyLength} bytes.",
                fullRawFrame: $binaryFrameContent,
                extractedMessageBody: $actualSiaBody, // Body was extracted, but length is wrong
                errorContext: ErrorContext::FRAME_VALIDATION,
                offsetWithinContext: $crcHeaderLength // Error relates to the length header's incorrectness
            );
        }

        // 7. Validate CRC
        // The CRC is calculated on the actualSiaBody (ID token through end of timestamp/extended data)
        $calculatedCrc = $this->crcCalculator->handle($actualSiaBody);

        if (strcasecmp($crcHeaderFromFrame, $calculatedCrc) !== 0) {
            Log::warning('CRC Mismatch', [
                'expected' => $crcHeaderFromFrame,
                'calculated' => $calculatedCrc,
                'body_hex_for_crc' => bin2hex($actualSiaBody),
            ]);
            throw new CrcMismatchException(
                message: "CRC mismatch. Frame CRC='{$crcHeaderFromFrame}', Calculated CRC='{$calculatedCrc}'.",
                expectedCrc: $crcHeaderFromFrame,
                calculatedCrc: $calculatedCrc,
                fullRawFrame: $binaryFrameContent, // The input to this action
                extractedMessageBody: $actualSiaBody // The body on which CRC was calculated
            );
        }

        // Log::debug("Frame CRC and Length validated successfully.", ['crc' => $crcHeaderFromFrame, 'length' => $declaredBodyLength]);

        return new ValidatedFrameDto(
            rawBody: $actualSiaBody,
            crcHeader: $crcHeaderFromFrame,
            lengthHeaderValue: $declaredBodyLength,
            rawFrame: $binaryFrameContent // Pass the original input binary frame for context
        );
    }
}
