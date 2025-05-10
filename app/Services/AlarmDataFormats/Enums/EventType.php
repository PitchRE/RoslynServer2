<?php

namespace App\Services\AlarmDataFormats\Enums;

/**
 * Defines the specific type of security event, providing more detail than EventCategory.
 * This is the "what" happened.
 */
enum EventType: string
{
    // --- BURGLARY ALARM TYPES ---
    case BURGLARY_PERIMETER = 'burglary_perimeter';   // Breach of external defenses (doors, windows).
    case BURGLARY_INTERIOR = 'burglary_interior';     // Intrusion detected within the protected area.
    case BURGLARY_24_HOUR = 'burglary_24_hour';       // Alarm from a zone that is always active.
    case BURGLARY_SILENT = 'burglary_silent';         // Burglary alarm with no local sounder.
    case BURGLARY_AUDIBLE = 'burglary_audible';       // Burglary alarm with local sounder.
    case BURGLARY_FOIL = 'burglary_foil';             // Alarm from window foil protection.
    case BURGLARY_GLASS_BREAK = 'burglary_glass_break'; // Alarm from glass break detector.
    case BURGLARY_MOTION_SENSOR = 'burglary_motion_sensor'; // Alarm from motion sensor.
    case BURGLARY_DOOR_WINDOW = 'burglary_door_window'; // Alarm from door/window contact.
    case BURGLARY_SAFE = 'burglary_safe';             // Alarm from a safe.

    // --- PANIC ALARM TYPES ---
    case PANIC_DURESS = 'panic_duress';         // User under duress (e.g., forced to disarm).
    case PANIC_HOLDUP = 'panic_holdup';         // Silent alarm indicating a robbery in progress.
    case PANIC_SILENT = 'panic_silent';         // General silent panic alarm.
    case PANIC_AUDIBLE = 'panic_audible';       // General audible panic alarm.
    case PANIC_KEYFOB = 'panic_keyfob';         // Panic initiated from a wireless keyfob.

    // --- FIRE ALARM TYPES ---
    case FIRE_SMOKE = 'fire_smoke';             // Smoke detector activation.
    case FIRE_HEAT = 'fire_heat';               // Heat detector activation.
    case FIRE_PULL_STATION = 'fire_pull_station'; // Manual fire alarm pull station activated.
    case FIRE_SPRINKLER = 'fire_sprinkler';       // Fire sprinkler system activation.
    case FIRE_DUCT_DETECTOR = 'fire_duct_detector'; // Smoke/heat detected in HVAC duct.
    case FIRE_CO_ALARM = 'fire_co_alarm';         // Carbon Monoxide alarm.

    // --- MEDICAL ALARM TYPES ---
    case MEDICAL_EMERGENCY = 'medical_emergency'; // General medical emergency.
    case MEDICAL_PENDANT = 'medical_pendant';     // Medical alarm from a wearable pendant.

    // --- ENVIRONMENTAL ALARM TYPES ---
    case ENV_GAS_LEAK = 'env_gas_leak';           // Detection of a gas leak.
    case ENV_FLOOD = 'env_flood';               // Water or flood detection.
    case ENV_HIGH_TEMP = 'env_high_temp';         // Temperature exceeds upper limit.
    case ENV_LOW_TEMP = 'env_low_temp';          // Temperature falls below lower limit.

    // --- TAMPER TYPES ---
    case TAMPER_PANEL = 'tamper_panel';           // Control panel enclosure opened or tampered.
    case TAMPER_DEVICE = 'tamper_device';         // Sensor or peripheral device tampered.
    case TAMPER_ZONE = 'tamper_zone';             // Tamper on a specific zone wiring/device.

    // --- SYSTEM OPERATION TYPES ---
    case SYSTEM_ARM_AWAY = 'system_arm_away';     // System armed in "away" mode (all sensors active).
    case SYSTEM_ARM_STAY = 'system_arm_stay';       // System armed in "stay" mode (perimeter active, interior bypassed).
    case SYSTEM_ARM_INSTANT = 'system_arm_instant'; // System armed with no entry delay.
    case SYSTEM_ARM_BY_USER = 'system_arm_by_user'; // System armed by a specific user.
    case SYSTEM_ARM_AUTO = 'system_arm_auto';       // System armed automatically by schedule.
    case SYSTEM_DISARM = 'system_disarm';           // System disarmed.
    case SYSTEM_DISARM_BY_USER = 'system_disarm_by_user'; // System disarmed by a specific user.
    case SYSTEM_DISARM_AUTO = 'system_disarm_auto';   // System disarmed automatically by schedule.
    case ZONE_BYPASS = 'zone_bypass';           // A specific zone has been bypassed.
    case ZONE_UNBYPASS = 'zone_unbypass';         // A specific zone is no longer bypassed.
    case SYSTEM_PROGRAMMING_ENTER = 'system_programming_enter'; // Entered installer/user programming mode.
    case SYSTEM_PROGRAMMING_EXIT = 'system_programming_exit';   // Exited programming mode.
    case USER_CODE_ADDED = 'user_code_added';       // New user code added to the system.
    case USER_CODE_DELETED = 'user_code_deleted';   // User code deleted from the system.
    case SYSTEM_REBOOT = 'system_reboot';         // Panel rebooted.

    // --- ACCESS CONTROL TYPES ---
    case ACCESS_GRANTED = 'access_granted';       // Access permitted (e.g., door unlocked).
    case ACCESS_DENIED = 'access_denied';         // Access attempted but denied.
    case DOOR_FORCED_OPEN = 'door_forced_open';   // Door opened without valid authorization.
    case DOOR_HELD_OPEN = 'door_held_open';       // Door propped or held open too long.

    // --- SYSTEM TROUBLE TYPES ---
    case TROUBLE_AC_LOSS = 'trouble_ac_loss';       // Loss of main AC power.
    case TROUBLE_LOW_BATTERY_PANEL = 'trouble_low_battery_panel'; // Control panel backup battery is low.
    case TROUBLE_LOW_BATTERY_DEVICE = 'trouble_low_battery_device'; // Wireless device battery is low.
    case TROUBLE_COMM_PRIMARY = 'trouble_comm_primary'; // Failure on primary communication path.
    case TROUBLE_COMM_BACKUP = 'trouble_comm_backup'; // Failure on backup communication path.
    case TROUBLE_PHONE_LINE = 'trouble_phone_line';   // Phone line fault (POTS).
    case TROUBLE_RF_JAMMING = 'trouble_rf_jamming';   // Radio frequency jamming detected.
    case TROUBLE_DEVICE_SUPERVISION = 'trouble_device_supervision'; // Wireless device missing or not responding.
    case TROUBLE_ZONE_FAULT = 'trouble_zone_fault';   // Wiring fault on a specific zone.
    case TROUBLE_FUSE_BLOWN = 'trouble_fuse_blown';   // Blown fuse in the system.
    case TROUBLE_GROUND_FAULT = 'trouble_ground_fault'; // Ground fault detected.
    case TROUBLE_SIREN = 'trouble_siren';           // Siren/bell circuit trouble.
    case TROUBLE_PRINTER = 'trouble_printer';       // Printer offline or paper out (for receivers).

    // --- MAINTENANCE SIGNAL TYPES ---
    case MAINT_TECHNICIAN_ON_SITE = 'maint_technician_on_site'; // Technician has arrived.
    case MAINT_TECHNICIAN_OFF_SITE = 'maint_technician_off_site'; // Technician has left.

    // --- TEST SIGNAL TYPES ---
    case TEST_MANUAL = 'test_manual';             // Test initiated manually by user/installer.
    case TEST_PERIODIC_AUTOMATIC = 'test_periodic_automatic'; // Scheduled automatic system test.
    case TEST_WALK = 'test_walk';                 // System in walk-test mode.
    case TEST_COMMUNICATION = 'test_communication'; // Communication path test.
    case TEST_BATTERY = 'test_battery';           // Battery test.

    // --- SUPERVISORY SYSTEM (CSR GENERATED) ---
    case SUPERVISORY_FAILURE_TO_ARM = 'supervisory_failure_to_arm';   // Site failed to arm by scheduled time.
    case SUPERVISORY_FAILURE_TO_DISARM = 'supervisory_failure_to_disarm'; // Site failed to disarm by scheduled time.
    case SUPERVISORY_COMM_TEST_FAIL = 'supervisory_comm_test_fail'; // Expected communication test not received.
    case SUPERVISORY_LATE_TO_OPEN = 'supervisory_late_to_open';   // Site not disarmed by expected opening time.
    case SUPERVISORY_EARLY_TO_CLOSE = 'supervisory_early_to_close'; // Site armed before expected closing time.

    // --- POWER EVENT TYPES (more specific than trouble) ---
    case POWER_AC_RESTORED = 'power_ac_restored';   // Main AC power restored.
    case POWER_BATTERY_OK = 'power_battery_ok';     // Panel battery condition normal.
    case POWER_BATTERY_DEVICE_OK = 'power_battery_device_ok'; // Device battery condition normal.

    // --- COMMUNICATION EVENT TYPES (more specific than trouble) ---
    case COMM_PRIMARY_RESTORED = 'comm_primary_restored'; // Primary communication path restored.
    case COMM_BACKUP_RESTORED = 'comm_backup_restored'; // Backup communication path restored.
    case COMM_PHONE_LINE_OK = 'comm_phone_line_ok'; // Phone line normal.

    // --- GENERIC / FALLBACK ---
    case GENERIC_ALARM = 'generic_alarm';         // A non-specific alarm condition.
    case GENERIC_TROUBLE = 'generic_trouble';       // A non-specific trouble condition.
    case GENERIC_EVENT = 'generic_event';         // A non-specific event.
    case UNKNOWN = 'unknown';                   // Event type could not be determined.
}
