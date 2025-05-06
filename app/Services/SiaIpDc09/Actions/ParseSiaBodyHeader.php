<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Data\ParsedHeaderDto;
use App\Services\SiaIpDc09\Data\ValidatedFrameDto;
use App\Services\SiaIpDc09\Enums\ErrorContext;
use App\Services\SiaIpDc09\Enums\SiaToken; // For SiaToken::tryFromWithEncryptionFlag
use App\Services\SiaIpDc09\Exceptions\GenericParsingException;
use App\Services\SiaIpDc09\Exceptions\UnsupportedElementException; // If we want to throw this for unknown tokens here
use Illuminate\Support\Facades\Log;

// If using lorisleiva/laravel-actions:
// use Lorisleiva\Actions\Concerns\AsAction;

class ParseSiaBodyHeader
{
    // If using lorisleiva/laravel-actions:
    // use AsAction;

    // Constants for field constraints based on SIA DC-09 Spec
    private const ID_TOKEN_MIN_LENGTH = 1; // e.g., "N"

    private const ID_TOKEN_MAX_LENGTH = 15; // Arbitrary reasonable max, spec not explicit, but tokens are short

    private const SEQUENCE_LENGTH = 4;

    private const RCVR_NUM_MIN_LEN = 1;

    private const RCVR_NUM_MAX_LEN = 6;

    private const LINE_PREFIX_MIN_LEN = 1; // L0 is valid

    private const LINE_PREFIX_MAX_LEN = 6;

    private const PANEL_ACCT_MIN_LEN = 3;

    private const PANEL_ACCT_MAX_LEN = 16;

    /**
     * Parses the header part of the SIA message body (ID, Seq, R, L, #).
     *
     * @param  ValidatedFrameDto  $validatedFrameDto  DTO containing the validated raw SIA body.
     * @return ParsedHeaderDto DTO containing parsed header fields and the remaining body content.
     *
     * @throws GenericParsingException If any header field is malformed or missing when mandatory.
     * @throws UnsupportedElementException If an ID token is parsed but not defined in SiaToken enum (optional check here).
     */
    public function handle(ValidatedFrameDto $validatedFrameDto): ParsedHeaderDto
    {
        $currentBody = $validatedFrameDto->rawBody;
        $offset = 0;
        $parsedHeaderForExceptionContext = []; // To build context for exceptions

        // --- 1. Parse ID Token ("id") ---
        // Example: "SIA-DCS" or "*ADM-CID"
        if ($offset >= strlen($currentBody) || $currentBody[$offset] !== '"') {
            throw new GenericParsingException(
                message: 'Body parsing error: Missing starting quote `"` for ID Token.',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset
            );
        }
        $idTokenStartOffset = $offset;
        $offset++; // Move past opening "

        $idTokenEndPos = strpos($currentBody, '"', $offset);
        if ($idTokenEndPos === false) {
            throw new GenericParsingException(
                message: 'Body parsing error: Missing ending quote `"` for ID Token.',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }

        $fullTokenString = substr($currentBody, $offset, $idTokenEndPos - $offset);
        if (strlen($fullTokenString) < self::ID_TOKEN_MIN_LENGTH || strlen($fullTokenString) > (self::ID_TOKEN_MAX_LENGTH + 1)) { // +1 for potential '*'
            throw new GenericParsingException(
                message: "Body parsing error: ID Token content '{$fullTokenString}' length is outside expected range.",
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }

        $wasEncrypted = false;
        $baseTokenEnum = SiaToken::tryFromWithEncryptionFlag($fullTokenString, $wasEncrypted);

        if ($baseTokenEnum === null) {
            // Token (after stripping '*') is not in our SiaToken Enum.
            // This could be an UnsupportedElementException or a GenericParsingException.
            // Let's use UnsupportedElementException as it's more specific for this case.
            // The main processing loop (HandleMessage/DetermineSiaResponse) might also check this against configured supported tokens.
            Log::warning('Parsed unknown base ID token.', ['token_string' => $fullTokenString, 'base_token_value' => $wasEncrypted ? substr($fullTokenString, 1) : $fullTokenString]);
            throw new UnsupportedElementException(
                message: "Body parsing error: Unknown or undefined base ID Token '{$fullTokenString}'.",
                unsupportedElementDescription: "ID Token: {$fullTokenString}",
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                parsedHeaderParts: $parsedHeaderForExceptionContext, // Empty at this stage
                offsetWithinContext: $idTokenStartOffset
            );
        }
        $protocolToken = $baseTokenEnum->value; // Store the base token value (e.g., "ADM-CID")
        $parsedHeaderForExceptionContext['protocol_token'] = $protocolToken;
        $parsedHeaderForExceptionContext['was_encrypted'] = $wasEncrypted;
        $offset = $idTokenEndPos + 1; // Move past closing "

        // --- 2. Parse Sequence Number (seq) ---
        // Example: 0001
        if (($offset + self::SEQUENCE_LENGTH) > strlen($currentBody)) {
            throw new GenericParsingException(
                message: 'Body parsing error: Insufficient data for Sequence Number.',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        $sequenceNumber = substr($currentBody, $offset, self::SEQUENCE_LENGTH);
        if (! ctype_digit($sequenceNumber) || strlen($sequenceNumber) !== self::SEQUENCE_LENGTH) {
            throw new GenericParsingException(
                message: "Body parsing error: Invalid Sequence Number format. Expected 4 digits, got '{$sequenceNumber}'.",
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        $parsedHeaderForExceptionContext['sequence_number'] = $sequenceNumber;
        $offset += self::SEQUENCE_LENGTH;

        // --- 3. Parse Receiver Number (Rrcvr) - Optional ---
        // Example: R123ABC
        $receiverNumber = null;
        if ($offset < strlen($currentBody) && $currentBody[$offset] === 'R') {
            $rcvrStartOffset = $offset;
            $numStart = $offset + 1;
            $numEnd = $numStart;
            $maxLength = $numStart + self::RCVR_NUM_MAX_LEN;

            while ($numEnd < strlen($currentBody) && $numEnd < $maxLength && ctype_xdigit($currentBody[$numEnd])) {
                $numEnd++;
            }
            $numLength = $numEnd - $numStart;

            if ($numLength >= self::RCVR_NUM_MIN_LEN) {
                $receiverNumber = substr($currentBody, $numStart, $numLength);
                $offset = $numEnd;
            } else {
                // It started with 'R' but didn't have valid following hex digits of min length.
                // This is a format error if 'R' is present.
                throw new GenericParsingException(
                    message: "Body parsing error: Malformed Receiver Number. Found 'R' but invalid/short hex digits followed.",
                    fullRawFrame: $validatedFrameDto->rawFrame,
                    extractedMessageBody: $validatedFrameDto->rawBody,
                    errorContext: ErrorContext::BODY_PARSING,
                    offsetWithinContext: $rcvrStartOffset,
                    parsedHeaderParts: $parsedHeaderForExceptionContext
                );
            }
        }
        $parsedHeaderForExceptionContext['receiver_number'] = $receiverNumber;

        // --- 4. Parse Line Prefix (Lpref) - Mandatory ---
        // Example: L0 or LFEDCBA
        if ($offset >= strlen($currentBody) || $currentBody[$offset] !== 'L') {
            throw new GenericParsingException(
                message: 'Body parsing error: Missing mandatory Line Prefix (L...).',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        $lprefStartOffset = $offset;
        $numStart = $offset + 1;
        $numEnd = $numStart;
        $maxLength = $numStart + self::LINE_PREFIX_MAX_LEN;

        while ($numEnd < strlen($currentBody) && $numEnd < $maxLength && ctype_xdigit($currentBody[$numEnd])) {
            $numEnd++;
        }
        $numLength = $numEnd - $numStart;

        if ($numLength >= self::LINE_PREFIX_MIN_LEN) {
            $linePrefix = substr($currentBody, $numStart, $numLength);
            $offset = $numEnd;
        } else {
            throw new GenericParsingException(
                message: 'Body parsing error: Malformed Line Prefix. Expected L followed by 1-6 hex digits, found insufficient valid digits.',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $lprefStartOffset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        $parsedHeaderForExceptionContext['line_prefix'] = $linePrefix;

        // --- 5. Parse Panel Account Number (#acct) - Mandatory ---
        // Example: #123456
        if ($offset >= strlen($currentBody) || $currentBody[$offset] !== '#') {
            throw new GenericParsingException(
                message: 'Body parsing error: Missing mandatory Panel Account Number (#...).',
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $offset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        $acctStartOffset = $offset;
        $numStart = $offset + 1;
        $numEnd = $numStart;
        $maxLength = $numStart + self::PANEL_ACCT_MAX_LEN;

        while ($numEnd < strlen($currentBody) && $numEnd < $maxLength && ctype_xdigit($currentBody[$numEnd])) {
            $numEnd++;
        }
        $numLength = $numEnd - $numStart;

        if ($numLength >= self::PANEL_ACCT_MIN_LEN && $numLength <= self::PANEL_ACCT_MAX_LEN) {
            $panelAccountNumber = substr($currentBody, $numStart, $numLength);
            $offset = $numEnd;
        } else {
            throw new GenericParsingException(
                message: 'Body parsing error: Malformed Panel Account Number. Expected # followed by '.self::PANEL_ACCT_MIN_LEN.'-'.self::PANEL_ACCT_MAX_LEN." hex digits, found {$numLength} valid digits.",
                fullRawFrame: $validatedFrameDto->rawFrame,
                extractedMessageBody: $validatedFrameDto->rawBody,
                errorContext: ErrorContext::BODY_PARSING,
                offsetWithinContext: $acctStartOffset,
                parsedHeaderParts: $parsedHeaderForExceptionContext
            );
        }
        // $parsedHeaderForExceptionContext['panel_account_number'] = $panelAccountNumber; // Already set above

        // --- 6. Remaining Content ---

        $remainingBodyContent = substr($currentBody, $offset);

        // Log::debug("SIA body header parsed successfully.", $parsedHeaderForExceptionContext + ['remaining_content_preview' => substr($remainingBodyContent, 0, 30)]);

        return new ParsedHeaderDto(
            rawFrame: $validatedFrameDto->rawFrame,
            rawBody: $validatedFrameDto->rawBody,
            protocolToken: $protocolToken,
            wasEncrypted: $wasEncrypted,
            sequenceNumber: $sequenceNumber,
            receiverNumber: $receiverNumber,
            linePrefix: $linePrefix,
            panelAccountNumber: $panelAccountNumber,
            remainingBodyContent: $remainingBodyContent
        );
    }
}
