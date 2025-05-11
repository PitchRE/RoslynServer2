<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Enums;

enum ProcessingStatus: string
{
    // --- Initial Reception & Basic Validation ---
    /** Message has been received by the listener but not yet validated. */
    case RECEIVED = 'received';

    /** Raw frame failed basic validation (e.g., CRC, length mismatch). NAK likely sent. Terminal for this message instance. */
    case FRAME_VALIDATION_FAILED = 'frame_validation_failed';

    /** Frame is valid, but SIA body parsing (header components, data field structure) failed. NAK likely sent. Terminal for this message instance. */
    case BODY_PARSING_FAILED = 'body_parsing_failed';

    /** Successfully parsed SIA DC-09 structure (header, data field extracted). Ready for protocol token interpretation. */
    case PARSED = 'parsed';

    // --- Protocol Token & Handler Resolution ---
    /** The protocol token from the message is not defined in the SiaToken Enum. Needs config/enum update. */
    case TOKEN_UNKNOWN = 'token_unknown';

    /** The protocol token is known, but no handler class is configured for it or the class doesn't exist. Needs config update. */
    case TOKEN_HANDLER_MISSING = 'token_handler_missing'; // Was TOKEN_NOT_SUPPORTED, more specific

    /** The protocol token is valid and a handler is configured, but the handler is currently disabled (e.g., by feature flag). */
    case TOKEN_HANDLER_DISABLED = 'token_handler_disabled'; // New, more specific than TOKEN_NOT_SUPPORTED

    // --- Payload Interpretation (by specific handler like InterpretAdemcoContactIdData) ---
    /** Interpretation of the token-specific payload (e.g., Contact ID string) is currently in progress by a handler. */
    case INTERPRETATION_IN_PROGRESS = 'interpretation_in_progress'; // New: Explicit "in progress"

    /** Interpretation of the token-specific payload failed (e.g., malformed Contact ID). Handler determined it's uninterpretable. */
    case INTERPRETATION_FAILED = 'interpretation_failed';

    /** Interpretation was successful, SecurityEvent (unsaved) populated with raw/mapped data. Ready for enrichment. */
    case INTERPRETED_READY_FOR_ENRICHMENT = 'interpreted_ready_for_enrichment'; // New: Clear state post-interpretation

    // --- Enrichment with Database Relations ---
    /** Enrichment of the interpreted SecurityEvent with database relations is in progress. */
    case ENRICHMENT_IN_PROGRESS = 'enrichment_in_progress'; // New

    /** Enrichment failed (e.g., DB error during lookups, critical data missing for lookups). */
    case ENRICHMENT_FAILED = 'enrichment_failed'; // New

    /** Enrichment successful. SecurityEvent has linked relations (or marked as PENDING_IDENTIFICATION). Ready for final save. */
    case ENRICHED_READY_FOR_SAVE = 'enriched_ready_for_save'; // New

    // --- Final Processing & SecurityEvent Creation ---
    /** Saving the final enriched SecurityEvent to the database failed. */
    case SECURITY_EVENT_SAVE_FAILED = 'security_event_save_failed'; // New

    /** SecurityEvent successfully created and saved. Processing of this SiaDc09Message is complete. */
    case PROCESSED_EVENT_CREATED = 'processed_event_created'; // Renamed from PROCESSED_SUCCESSFULLY for clarity

    // --- Specific NAK Responses (if you log the SIA message even when NAKing) ---
    // These might overlap with FRAME_VALIDATION_FAILED or BODY_PARSING_FAILED,
    // but can be more specific if the NAK reason is determined after basic parsing.
    case NAK_SENT_TIMESTAMP_REJECT = 'nak_sent_timestamp_reject'; // NAK sent due to timestamp out of tolerance.

    // --- Other Terminal / Special States ---
    /** Message was a NULL message (link test) and handled, no SecurityEvent created. */
    case PROCESSED_NULL_MESSAGE = 'processed_null_message'; // New

    /** Message was processed, but determined to be a duplicate or not requiring a new SecurityEvent. */
    case ARCHIVED_NO_EVENT = 'archived_no_event'; // New

    /** An unexpected/unhandled error occurred during processing by a handler or subsequent step. */
    case PROCESSING_UNEXPECTED_ERROR = 'processing_unexpected_error'; // Replaces PROCESSING_HANDLER_ERROR for broader scope

    /** Processing failed in a way that is deemed permanent for this message instance (e.g., fundamentally corrupt). */
    case PROCESSING_FAILED_PERMANENTLY = 'processing_failed_permanently'; // New: For non-retryable errors.

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Received',
            self::FRAME_VALIDATION_FAILED => 'Frame Validation Failed',
            self::BODY_PARSING_FAILED => 'Body Parsing Failed',
            self::PARSED => 'Parsed (SIA Structure OK)',
            self::TOKEN_UNKNOWN => 'Token Unknown',
            self::TOKEN_HANDLER_MISSING => 'Token Handler Missing/Misconfigured',
            self::TOKEN_HANDLER_DISABLED => 'Token Handler Disabled',
            self::INTERPRETATION_IN_PROGRESS => 'Interpretation in Progress',
            self::INTERPRETATION_FAILED => 'Payload Interpretation Failed',
            self::INTERPRETED_READY_FOR_ENRICHMENT => 'Interpreted - Ready for Enrichment',
            self::ENRICHMENT_IN_PROGRESS => 'Enrichment in Progress',
            self::ENRICHMENT_FAILED => 'Enrichment Failed',
            self::ENRICHED_READY_FOR_SAVE => 'Enriched - Ready for Save',
            self::SECURITY_EVENT_SAVE_FAILED => 'SecurityEvent Save Failed',
            self::PROCESSED_EVENT_CREATED => 'Processed - SecurityEvent Created',
            self::NAK_SENT_TIMESTAMP_REJECT => 'NAK Sent (Timestamp Rejected)',
            self::PROCESSED_NULL_MESSAGE => 'Processed - Null Message Handled',
            self::ARCHIVED_NO_EVENT => 'Archived - No Event Created',
            self::PROCESSING_UNEXPECTED_ERROR => 'Processing - Unexpected Error',
            self::PROCESSING_FAILED_PERMANENTLY => 'Processing Failed (Permanent)',
            //    default => str_replace('_', ' ', ucwords(strtolower($this->value), '_')),
        };
    }

    /**
     * Statuses that indicate the message is in a valid state to attempt interpretation and event creation routing.
     */
    public static function eligibleForRouting(): array
    {
        return [
            self::PARSED,
            // Potentially add retryable error states if RouteSiaMessage also handles retries from these points:
            // self::INTERPRETATION_FAILED,
            // self::ENRICHMENT_FAILED,
            // self::SECURITY_EVENT_SAVE_FAILED,
            // self::PROCESSING_UNEXPECTED_ERROR, // If the error was transient
        ];
    }

    /**
     * Statuses that indicate a final, successful processing outcome.
     */
    public static function successfulOutcomes(): array
    {
        return [
            self::PROCESSED_EVENT_CREATED,
            self::PROCESSED_NULL_MESSAGE,
            self::ARCHIVED_NO_EVENT,
        ];
    }

    /**
     * Statuses that indicate a terminal failure for this message instance (unlikely to succeed on simple retry).
     */
    public static function terminalFailureOutcomes(): array
    {
        return [
            self::FRAME_VALIDATION_FAILED,
            self::BODY_PARSING_FAILED,
            self::TOKEN_UNKNOWN, // Unless token list can be updated and retried
            self::TOKEN_HANDLER_MISSING, // Unless config can be updated and retried
            // self::INTERPRETATION_FAILED, // Could be terminal if data is fundamentally malformed
            self::PROCESSING_FAILED_PERMANENTLY,
        ];
    }
}
