<?php

namespace App\Enums;

enum AlarmEventQualifier: string
{
    // Alarm Conditions
    case ALARM = 'alarm'; // A new alarm event (CID Q=1)
    case ALARM_RESTORE = 'alarm_restore'; // Restore from alarm (CID Q=3)
    case ALARM_CANCEL = 'alarm_cancel'; // Alarm cancelled by user

    // Trouble Conditions
    case TROUBLE = 'trouble'; // A new trouble condition (CID Q=1)
    case TROUBLE_RESTORE = 'trouble_restore'; // Restore from trouble (CID Q=3)

    // System Activity
    case OPENING = 'opening'; // Panel disarmed (CID Q=1)
    case CLOSING = 'closing'; // Panel armed (CID Q=3)
    case BYPASS = 'bypass';
    case BYPASS_RESTORE = 'bypass_restore';
    case TEST_START = 'test_start';
    case TEST_END = 'test_end';
    case TEST_REPORT = 'test_report'; // Periodic test etc.

    // General Status / Info
    case STATUS_REPORT = 'status_report';
    case EVENT = 'event'; // Generic non-alarm/trouble event
    case RESTORE = 'restore'; // Generic restore if type unknown

    // If mapping CID Q=6
    // case CONDITION_PRESENT = 'condition_present';

    case UNKNOWN = 'unknown';
}
