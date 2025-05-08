<?php

namespace App\Enums;

enum AlarmEventCategory: string
{
    case BURGLARY = 'burglary';
    case FIRE = 'fire';
    case MEDICAL = 'medical';
    case PANIC = 'panic';
    case ENVIRONMENTAL = 'environmental'; // e.g., Gas, Water, Temp
    case SYSTEM_TROUBLE = 'system_trouble'; // e.g., AC Fail, Low Bat, Comm Fail
    case SYSTEM_ACTIVITY = 'system_activity'; // e.g., Open, Close, Test, Bypass
    case ACCESS_CONTROL = 'access_control'; // If handling AC events
    case OTHER = 'other';
    case UNKNOWN = 'unknown';
}
