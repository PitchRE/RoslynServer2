<?php

declare(strict_types=1);

namespace App\Services\AlarmDataFormats\Enums;

/**
 * Broad categorization of interpreted events.
 */
enum EventCategory: string
{
    case ALARM = 'alarm';                 // Burglar, Fire, Panic, Medical, Environmental Alarms etc.
    case TROUBLE = 'trouble';               // Sensor trouble, System trouble (AC Fail, Low Batt), Comm Fail
    case ACCESS_CONTROL = 'access_control'; // Opening, Closing, Access Denied/Granted (maybe split further?)
    case SYSTEM = 'system';               // Maintenance, Walk Test, Config Change, Reboot, Link Test (if needed)
    case TEST = 'test';                   // Manual Test Report, Periodic Test
    case INFORMATION = 'information';       // General status or info not fitting above
    case UNKNOWN = 'unknown';               // Cannot be categorized
}
