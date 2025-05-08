<?php

namespace App\Services\AlarmDataFormats\Enums;

/**
 * Defines the general category or high-level classification of a security event.
 * This helps in broad grouping and initial triage.
 */
enum EventCategory: string
{
    // Alarm Categories
    case ALARM_BURGLARY = 'alarm_burglary'; // Intrusion or unauthorized entry attempt.
    case ALARM_PANIC = 'alarm_panic';       // Manually triggered distress signal (e.g., duress, hold-up).
    case ALARM_FIRE = 'alarm_fire';         // Detection of fire, smoke, or excessive heat.
    case ALARM_MEDICAL = 'alarm_medical';   // Request for medical assistance.
    case ALARM_ENVIRONMENTAL = 'alarm_environmental'; // Gas leak, flood, temperature extremes.
    case ALARM_TAMPER = 'alarm_tamper';     // Physical interference with security equipment.
    case ALARM_TECHNICAL = 'alarm_technical'; // Critical system malfunction requiring immediate attention (distinct from TROUBLE).

    // System Operation & Access Categories
    case SYSTEM_OPERATION = 'system_operation'; // Standard operational events like arming, disarming.
    case ACCESS_CONTROL = 'access_control';   // Events related to physical access (door entry, gate access).

    // Trouble & Maintenance Categories
    case SYSTEM_TROUBLE = 'system_trouble';   // Non-critical system faults or issues needing attention.
    case MAINTENANCE_SIGNAL = 'maintenance_signal'; // Signals related to system upkeep or service.

    // Test Categories
    case TEST_SIGNAL = 'test_signal';       // Events generated during system testing.

    // Informational & Supervisory Categories
    case INFORMATIONAL = 'informational';     // General non-critical information.
    case SUPERVISORY_SYSTEM = 'supervisory_system'; // Backend/CSR generated supervisory events (e.g., failure to arm by schedule).

    // Utility and other specific categories
    case POWER_EVENT = 'power_event';       // Related to AC power status or battery issues.
    case COMMUNICATION_EVENT = 'communication_event'; // Related to communication path status.

    // Fallback or Unknown
    case UNKNOWN = 'unknown';             // Category could not be determined.
}