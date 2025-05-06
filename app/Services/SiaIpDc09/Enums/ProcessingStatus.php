<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Enums;

enum ProcessingStatus: string
{
    /** Initial state when a message is received but not yet passed basic frame validation. */
    case RECEIVED = 'received';

    /** Basic frame structure (CRC, Length) parsing failed. */
    case FRAME_VALIDATION_FAILED = 'frame_validation_failed';

    /** SIA header and content parsing failed (after frame validation passed). */
    case BODY_PARSING_FAILED = 'body_parsing_failed';

    /** Message was successfully parsed into its SIA DC-09 components. Ready for interpretation. */
    case PARSED = 'parsed';

    /** The protocol token is known but currently not supported/enabled in the system. */
    case TOKEN_NOT_SUPPORTED = 'token_not_supported';

    /** The protocol token is unknown/not defined in the SiaToken Enum. */
    case TOKEN_UNKNOWN = 'token_unknown';

    /** Interpretation of the message_data (e.g., Contact ID) failed. */
    case INTERPRETATION_FAILED = 'interpretation_failed';

    /** An unexpected error occurred during the routing or execution of an interpretation handler. */
    case PROCESSING_HANDLER_ERROR = 'processing_handler_error';

    /** Message has been successfully parsed and its data content interpreted. */
    case PROCESSED_SUCCESSFULLY = 'processed_successfully';

    /** A response was successfully sent for this message. */
    // case RESPONSE_SENT = 'response_sent'; // Optional, if you want to track this explicitly

    /** Message processing has been completed, but resulted in a state where no further action is taken (e.g. DUH sent for unsupported) */
    // case COMPLETED_WITH_ERROR = 'completed_with_error'; // Optional
}
