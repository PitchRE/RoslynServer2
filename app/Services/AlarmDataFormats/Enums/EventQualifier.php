<?php

namespace App\Services\AlarmDataFormats\Enums;

// Refine or expand based on needs across different formats
enum EventQualifier: string // Changed backing to string for more flexibility
{
    // Common CID Qualifiers
    case NEW_EVENT = 'new_event'; // CID 1 (Alarms, Troubles, Status)
    case RESTORE = 'restore';   // CID 3 (Restorals)
    case OPENING = 'opening';   // CID 1 (Usually linked to specific User Closing codes)
    case CLOSING = 'closing';   // CID 3 (Usually linked to specific User Opening codes)

    // Potential additions
    case INFORMATION = 'information'; // General info
    case START = 'start';         // E.g., Test Start, Walk Test Start
    case END = 'end';             // E.g., Test End, Walk Test End
    case PERIODIC = 'periodic';     // E.g., Periodic Test, System Status Update
    case MANUAL = 'manual';       // E.g., Manual Trigger (like manual test)
    case CONDITION_PRESENT = 'condition_present'; // CID 6 - often unused but standard

    case UNKNOWN = 'unknown';     // Qualifier could not be determined

    // Helper based on CID origin (example)
    public static function fromContactId(int $cidQualifier): self
    {
        return match ($cidQualifier) {
            1 => self::NEW_EVENT, // Could refine later based on event code (Opening vs New Alarm)
            3 => self::RESTORE, // Could refine later (Closing vs Restore)
            6 => self::CONDITION_PRESENT,
            default => self::UNKNOWN,
        };
    }

    // Refine label() method if needed based on string values
    public function label(): string
    {
        return str_replace('_', ' ', $this->value); // Simple default label
    }
}
