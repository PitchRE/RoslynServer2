<?php

namespace App\Enums;

enum AlarmEventPriority: int
{ // Use int for easy sorting/comparison
    case CRITICAL = 1; // Highest priority (e.g., Fire, Panic, Medical)
    case HIGH = 2;     // Serious alarms (e.g., Burglary)
    case MEDIUM = 3;   // Important troubles affecting security (e.g., AC Fail, Comm Fail)
    case LOW = 4;      // Less critical troubles, bypasses, non-urgent system events
    case INFORMATIONAL = 5; // Tests, Open/Close (can depend on site rules)
    case LOG = 6;      // Lowest priority, purely for logging
}
