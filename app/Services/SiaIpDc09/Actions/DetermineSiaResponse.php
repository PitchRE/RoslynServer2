<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use App\Services\SiaIpDc09\Enums\ResponseType;
use App\Services\SiaIpDc09\Enums\SiaToken;
use App\Services\SiaIpDc09\Exceptions\CrcMismatchException;
use App\Services\SiaIpDc09\Exceptions\DecryptionErrorException;
use App\Services\SiaIpDc09\Exceptions\GenericParsingException;
use App\Services\SiaIpDc09\Exceptions\InvalidFrameException;
use App\Services\SiaIpDc09\Exceptions\SiaMessageException;
use App\Services\SiaIpDc09\Exceptions\TimestampInvalidException;
use App\Services\SiaIpDc09\Exceptions\UnsupportedElementException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction; // For updating model status

// Processing Exception

class DetermineSiaResponse
{
    use AsAction;

    /**
     * Determines the appropriate SIA DC-09 response.
     *
     * @param  ?SiaDc09Message  $siaMessage  The Eloquent model (populated on parse success, or partially on failure).
     * @param  ?SiaMessageException  $parsingException  The exception caught during parsing, if any.
     * @return ResponseType The determined response type.
     */
    public function handle(?SiaDc09Message $siaMessage, ?SiaMessageException $parsingException): ResponseType
    {

        $logContext = [
            'message_id' => $siaMessage?->id,
            'exception_class' => $parsingException ? get_class($parsingException) : null,
            'panel_account_number' => $siaMessage->panel_account_number ?? $parsingException?->getParsedHeaderParts()['panel_account_number'] ?? 'unknown',
            'protocol_token_from_msg' => $siaMessage->protocol_token ?? $parsingException?->getParsedHeaderParts()['protocol_token'] ?? 'unknown',
        ];

        // --- Handle Failures First (based on parsingException) ---
        if ($parsingException) {
            if ($parsingException instanceof CrcMismatchException || $parsingException instanceof DecryptionErrorException) {
                Log::info('DetermineSiaResponse: CRC or Decryption error. No response.', $logContext);

                // Model status updated by ParseMessageFrame
                return ResponseType::NONE;
            }

            if ($parsingException instanceof InvalidFrameException) {
                Log::info('DetermineSiaResponse: Invalid frame structure error. No response.', $logContext);

                // Model status updated by ParseMessageFrame
                return ResponseType::NONE;
            }

            if ($parsingException instanceof TimestampInvalidException) {
                Log::info('DetermineSiaResponse: Timestamp invalid. Responding NAK.', $logContext);

                // Model status updated by ParseMessageFrame
                return ResponseType::NAK;
            }

            if ($parsingException instanceof UnsupportedElementException) {
                // This was thrown by ParseSiaBodyHeader because the base token string isn't in SiaToken enum.
                // The config 'reject_unknown_tokens' applies here.
                if (Config::get('SiaIpDc09.behavior.reject_unknown_tokens', true)) {
                    Log::warning('DetermineSiaResponse: Unsupported/Unknown ID Token (not in Enum). Responding DUH based on config.', $logContext + ['element' => $parsingException->getUnsupportedElementDescription()]);
                    if ($siaMessage) { // Update status if model exists
                        $siaMessage->processing_status = ProcessingStatus::TOKEN_UNKNOWN;
                        // processing_notes already set by ParseMessageFrame with exception context
                        $siaMessage->saveQuietly();
                    }

                    return ResponseType::DUH;
                } else {
                    // Log Acknowledging an unknown token format might be risky.
                    // Log::info("DetermineSiaResponse: Unsupported/Unknown ID Token, but config allows ACK (not recommended). Responding ACK.", $logContext);
                    // return ResponseType::ACK; // This path is generally not advised.
                    // Defaulting to DUH if not explicitly rejecting is safer than ACK.
                    Log::warning('DetermineSiaResponse: Unsupported/Unknown ID Token. Configured not to reject, but DUH is safer than ACK.', $logContext);
                    if ($siaMessage) {
                        $siaMessage->processing_status = ProcessingStatus::TOKEN_UNKNOWN;
                        $siaMessage->saveQuietly();
                    }

                    return ResponseType::DUH;
                }
            }

            // For other GenericParsingException or general SiaMessageException from body parsing
            Log::warning('DetermineSiaResponse: General parsing error. Responding DUH.', $logContext + ['error' => $parsingException->getMessage()]);

            // Model status updated by ParseMessageFrame
            return ResponseType::DUH;
        }

        // --- Handle Success (siaMessage model is populated and PARSED) ---
        if ($siaMessage && $siaMessage->processing_status === ProcessingStatus::PARSED) {
            $tokenString = $siaMessage->protocol_token; // Base token string
            $tokenEnumCase = SiaToken::tryFrom($tokenString ?? ''); // Try to get enum case

            if ($tokenEnumCase === null) {
                // This case should ideally be caught by ParseSiaBodyHeader throwing UnsupportedElementException
                // if the token isn't in the SiaToken enum. This is a defensive check.
                if (Config::get('SiaIpDc09.behavior.reject_unknown_tokens', true)) {
                    Log::error("DetermineSiaResponse: Successfully parsed message but token '{$tokenString}' is not defined in SiaToken enum. Responding DUH.", $logContext);
                    $siaMessage->processing_status = ProcessingStatus::TOKEN_UNKNOWN;
                    $siaMessage->processing_notes = "Token '{$tokenString}' not defined in SiaToken enum.";
                    $siaMessage->saveQuietly();

                    return ResponseType::DUH;
                }
                // If not rejecting, proceed, but this is risky.
            }

            // Check if the token is configured in SiaIpDc09.supported_tokens
            $tokenConfig = Config::get('SiaIpDc09.supported_tokens.'.$tokenString);

            if ($tokenConfig === null) {
                // Token is known by Enum but not listed in supported_tokens config (effectively unsupported)
                if (Config::get('SiaIpDc09.behavior.reject_unsupported_configured_tokens', true)) { // Check this config
                    Log::warning("DetermineSiaResponse: Token '{$tokenString}' is known by Enum but not configured in 'SiaIpDc09.supported_tokens'. Responding DUH.", $logContext);
                    $siaMessage->processing_status = ProcessingStatus::TOKEN_NOT_SUPPORTED;
                    $siaMessage->processing_notes = "Token '{$tokenString}' not configured as supported.";
                    $siaMessage->saveQuietly();

                    return ResponseType::DUH;
                }
            } elseif (empty($tokenConfig['handler_class'])) {
                // Token is in supported_tokens config, but no handler_class is defined (or it's null)
                if (Config::get('SiaIpDc09.behavior.reject_unsupported_configured_tokens', true)) {
                    Log::warning("DetermineSiaResponse: Token '{$tokenString}' is configured but has no handler_class. Responding DUH.", $logContext);
                    $siaMessage->processing_status = ProcessingStatus::TOKEN_NOT_SUPPORTED;
                    $siaMessage->processing_notes = "Token '{$tokenString}' is configured with no interpretation handler.";
                    $siaMessage->saveQuietly();

                    return ResponseType::DUH;
                }
            }

            // If we reach here, the token is either fully supported or configured not to be rejected.
            // Now, check timestamp tolerance for encrypted messages that were successfully parsed.
            if ($siaMessage->was_encrypted && $siaMessage->sia_timestamp) {
                $nowUtc = CarbonImmutable::now('UTC');
                $messageTimestamp = $siaMessage->sia_timestamp; // Already CarbonImmutable UTC

                $diffInSeconds = $nowUtc->diffInSeconds($messageTimestamp, false); // Signed difference

                $toleranceFuture = Config::get('SiaIpDc09.timestamp_tolerance.future_seconds', 20);
                $tolerancePast = Config::get('SiaIpDc09.timestamp_tolerance.past_seconds', 40);

                if ($diffInSeconds > $toleranceFuture || $diffInSeconds < (-1 * $tolerancePast)) {
                    Log::warning('DetermineSiaResponse: Timestamp tolerance check failed for encrypted message. Responding NAK.', $logContext + [
                        'message_time' => $messageTimestamp->toIso8601String(),
                        'server_time' => $nowUtc->toIso8601String(),
                        'difference_seconds' => $diffInSeconds,
                        'tolerance' => "+{$toleranceFuture}/-{$tolerancePast} sec",
                    ]);

                    // Note: TimestampInvalidException is usually for *parsing* errors of the timestamp.
                    // Here, it's a *validation* failure post-parsing. NAK is still correct.
                    // We don't need to throw an exception, just determine NAK.
                    // Model status PARSED is okay, NAK is a valid response to a parsed message.
                    return ResponseType::NAK;
                }
                Log::debug('DetermineSiaResponse: Timestamp tolerance check passed for encrypted message.', $logContext);
            }

            // If all checks pass for a successfully parsed message
            Log::info('DetermineSiaResponse: Message parsed and validated. Responding ACK.', $logContext);

            return ResponseType::ACK;
        }

        // Fallback: Should ideally not be reached if $parsingException or $siaMessage is always provided by HandleMessage
        Log::error('DetermineSiaResponse: Reached fallback state (no exception, no parsed message, or message not in PARSED state). No response.', $logContext);
        if ($siaMessage && $siaMessage->processing_status !== ProcessingStatus::PARSED) {
            Log::error('DetermineSiaResponse: Message provided but not in PARSED state.', $logContext + ['actual_status' => $siaMessage->processing_status->value]);
        }

        return ResponseType::NONE;
    }
}
