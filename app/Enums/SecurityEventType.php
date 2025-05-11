<?php

// app/Enums/SecurityEventType.php

namespace App\Enums;

enum SecurityEventType: string
{
    case OP_SYSTEM_ARM_DISARM_BY_USER = 'op_system_arm_disarm_by_user'; // General Arm/Disarm by user, specific determined by qualifier
    // --- BURGLARY ALARM TYPES ---
    case BURGLARY_PERIMETER = 'burglary_perimeter';   // Breach of external defenses (doors, windows).
    case BURGLARY_INTERIOR = 'burglary_interior';     // Intrusion detected within the protected area.
    case BURGLARY_24_HOUR = 'burglary_24_hour';       // Alarm from a zone that is always active (e.g., panic, safe).
    case BURGLARY_SILENT_ALARM = 'burglary_silent_alarm'; // Burglary alarm with no local sounder.
    case BURGLARY_AUDIBLE_ALARM = 'burglary_audible_alarm'; // Burglary alarm with local sounder.
    case BURGLARY_FOIL_BREAK = 'burglary_foil_break';   // Alarm from window foil protection being broken.
    case BURGLARY_GLASS_BREAK_DETECTOR = 'burglary_glass_break_detector'; // Alarm from acoustic glass break detector.
    case BURGLARY_MOTION_DETECTED = 'burglary_motion_detected'; // Alarm from a motion sensor.
    case BURGLARY_DOOR_WINDOW_OPENED = 'burglary_door_window_opened'; // Alarm from a door/window contact.
    case BURGLARY_SAFE_TAMPER_OPEN = 'burglary_safe_tamper_open'; // Alarm from a safe being tampered with or opened.
    case BURGLARY_ASSET_PROTECTION = 'burglary_asset_protection'; // Alarm from a sensor protecting a specific asset.

    // --- PANIC / DURESS ALARM TYPES ---
    case PANIC_DURESS_CODE_USED = 'panic_duress_code_used'; // User entered a special duress code to disarm, signaling they are under threat.
    case PANIC_HOLDUP_BUTTON = 'panic_holdup_button';     // Silent alarm indicating a robbery in progress, typically from a fixed button.
    case PANIC_SILENT_MANUAL = 'panic_silent_manual';   // General silent panic alarm triggered manually.
    case PANIC_AUDIBLE_MANUAL = 'panic_audible_manual';  // General audible panic alarm triggered manually.
    case PANIC_KEYFOB_ACTIVATION = 'panic_keyfob_activation'; // Panic initiated from a wireless keyfob.
    case PANIC_AMBUSH = 'panic_ambush';               // Similar to duress, often used if forced to arm/disarm upon entry/exit.

    // --- FIRE ALARM TYPES ---
    case FIRE_SMOKE_DETECTOR = 'fire_smoke_detector';   // Smoke detector activation.
    case FIRE_HEAT_DETECTOR = 'fire_heat_detector';     // Heat detector activation.
    case FIRE_MANUAL_PULL_STATION = 'fire_manual_pull_station'; // Manual fire alarm pull station activated.
    case FIRE_SPRINKLER_WATERFLOW = 'fire_sprinkler_waterflow'; // Fire sprinkler system water flow detected.
    case FIRE_DUCT_DETECTOR_ACTIVATION = 'fire_duct_detector_activation'; // Smoke/heat detected in HVAC duct.
    case FIRE_CARBON_MONOXIDE = 'fire_carbon_monoxide'; // Carbon Monoxide (CO) detector activation.
    case FIRE_FLAME_DETECTOR = 'fire_flame_detector';   // Flame detector activation.

    // --- MEDICAL ALARM TYPES ---
    case MEDICAL_EMERGENCY_ASSISTANCE = 'medical_emergency_assistance'; // General medical emergency request.
    case MEDICAL_PENDANT_ACTIVATION = 'medical_pendant_activation';   // Medical alarm from a wearable pendant.
    case MEDICAL_FALL_DETECTED = 'medical_fall_detected';           // Medical alarm triggered by a fall detector.

    // --- ENVIRONMENTAL HAZARD TYPES ---
    case ENV_GAS_LEAK_DETECTED = 'env_gas_leak_detected';       // Detection of a natural gas, propane, or other combustible gas leak.
    case ENV_FLOOD_WATER_DETECTED = 'env_flood_water_detected';   // Water or flood detection.
    case ENV_HIGH_TEMPERATURE_LIMIT = 'env_high_temperature_limit'; // Temperature exceeds a critical upper limit (e.g., server room, freezer failure).
    case ENV_LOW_TEMPERATURE_LIMIT = 'env_low_temperature_limit';  // Temperature falls below a critical lower limit (e.g., freeze protection).
    case ENV_HUMIDITY_OUT_OF_RANGE = 'env_humidity_out_of_range'; // Humidity level is too high or too low.
    case ENV_REFRIGERATION_FAILURE = 'env_refrigeration_failure'; // Specific alarm for refrigeration unit failure.

    // --- SYSTEM TAMPER TYPES ---
    case TAMPER_CONTROL_PANEL = 'tamper_control_panel';     // Control panel enclosure opened or tampered with.
    case TAMPER_DEVICE_SENSOR = 'tamper_device_sensor';     // Sensor or peripheral device enclosure opened or tampered.
    case TAMPER_ZONE_WIRING = 'tamper_zone_wiring';       // Tamper detected on a specific zone's wiring.
    case TAMPER_KEYPAD = 'tamper_keypad';               // Keypad tampered with.
    case TAMPER_SIREN_BELL = 'tamper_siren_bell';         // External sounder tampered with.

    // --- CRITICAL TECHNICAL ALARM TYPES ---
    case TECHNICAL_PANEL_CPU_FAILURE = 'technical_panel_cpu_failure'; // Critical CPU or mainboard failure in the panel.
    case TECHNICAL_MEMORY_CORRUPTION = 'technical_memory_corruption'; // Panel memory (RAM/ROM) corruption.
    case TECHNICAL_SYSTEM_LOCKOUT = 'technical_system_lockout';   // System locked out due to too many failed attempts or critical error.
    case TECHNICAL_POWER_SUPPLY_OVERLOAD = 'technical_power_supply_overload'; // Panel power supply unit is overloaded.

    // --- SYSTEM OPERATION / ACCESS TYPES ---
    case OP_SYSTEM_ARM_AWAY = 'op_system_arm_away';           // System armed in "away" mode (all sensors active).
    case OP_SYSTEM_ARM_STAY = 'op_system_arm_stay';             // System armed in "stay" mode (perimeter active, interior typically bypassed).
    case OP_SYSTEM_ARM_INSTANT = 'op_system_arm_instant';       // System armed with no entry delay.
    case OP_SYSTEM_ARM_BY_USER = 'op_system_arm_by_user';       // System armed by a specific user code.
    case OP_SYSTEM_ARM_BY_KEYFOB = 'op_system_arm_by_keyfob';   // System armed via a wireless keyfob.
    case OP_SYSTEM_ARM_AUTO_SCHEDULE = 'op_system_arm_auto_schedule'; // System armed automatically by a pre-set schedule.
    case OP_SYSTEM_DISARM_BY_USER = 'op_system_disarm_by_user';  // System disarmed by a specific user code.
    case OP_SYSTEM_DISARM_BY_KEYFOB = 'op_system_disarm_by_keyfob'; // System disarmed via a wireless keyfob.
    case OP_SYSTEM_DISARM_AUTO_SCHEDULE = 'op_system_disarm_auto_schedule'; // System disarmed automatically by schedule.
    case OP_ZONE_BYPASSED = 'op_zone_bypassed';               // A specific zone has been manually bypassed.
    case OP_ZONE_UNBYPASSED = 'op_zone_unbypassed';             // A specific zone is no longer bypassed.
    case OP_SYSTEM_PROGRAMMING_ENTERED = 'op_system_programming_entered'; // Entered installer or user programming mode.
    case OP_SYSTEM_PROGRAMMING_EXITED = 'op_system_programming_exited';  // Exited programming mode.
    case OP_USER_CODE_MANAGEMENT = 'op_user_code_management'; // User code added, deleted, or changed.
    case OP_SYSTEM_REBOOT_OR_RESET = 'op_system_reboot_or_reset'; // Control panel was rebooted or reset.
    case OP_DOOR_ACCESS_GRANTED = 'op_door_access_granted';   // Access permitted through a controlled door.
    case OP_DOOR_ACCESS_DENIED = 'op_door_access_denied';     // Access attempted at a controlled door but denied.
    case OP_DOOR_FORCED_OPEN_ALARM = 'op_door_forced_open_alarm'; // Controlled door was forced open.
    case OP_DOOR_HELD_OPEN_ALARM = 'op_door_held_open_alarm';  // Controlled door was held open too long.
    case OP_SYSTEM_OUTPUT_ACTIVATED = 'op_system_output_activated'; // A programmable output on the panel was activated.
    case OP_SYSTEM_OUTPUT_DEACTIVATED = 'op_system_output_deactivated'; // A programmable output was deactivated.
    case OP_REMOTE_SESSION_START = 'op_remote_session_start'; // Remote access/programming session started.
    case OP_REMOTE_SESSION_END = 'op_remote_session_end';   // Remote access/programming session ended.

    // --- SYSTEM TROUBLE (NON-CRITICAL) TYPES ---
    case TROUBLE_AC_POWER_LOSS = 'trouble_ac_power_loss';         // Loss of main AC power to the panel.
    case TROUBLE_PANEL_LOW_BATTERY = 'trouble_panel_low_battery';   // Control panel backup battery is low.
    case TROUBLE_DEVICE_LOW_BATTERY = 'trouble_device_low_battery'; // Wireless sensor or peripheral device battery is low.
    case TROUBLE_COMM_PATH_PRIMARY = 'trouble_comm_path_primary'; // Failure on the primary communication path (e.g., IP, cellular).
    case TROUBLE_COMM_PATH_BACKUP = 'trouble_comm_path_backup';  // Failure on the backup communication path.
    case TROUBLE_PHONE_LINE_FAULT = 'trouble_phone_line_fault';    // Fault detected on the POTS phone line.
    case TROUBLE_RF_JAMMING_DETECTED = 'trouble_rf_jamming_detected'; // Radio frequency jamming detected, affecting wireless devices.
    case TROUBLE_DEVICE_SUPERVISION_LOSS = 'trouble_device_supervision_loss'; // Wireless device is missing or not responding (check-in fail).
    case TROUBLE_ZONE_WIRING_FAULT = 'trouble_zone_wiring_fault';  // Wiring fault (open, short) on a specific hardwired zone.
    case TROUBLE_PANEL_FUSE_BLOWN = 'trouble_panel_fuse_blown';    // A fuse within the control panel or power supply has blown.
    case TROUBLE_GROUND_FAULT_DETECTED = 'trouble_ground_fault_detected'; // Ground fault detected in the system wiring.
    case TROUBLE_SIREN_BELL_CIRCUIT = 'trouble_siren_bell_circuit'; // Trouble with the siren or bell output circuit.
    case TROUBLE_EXPANSION_MODULE_OFFLINE = 'trouble_expansion_module_offline'; // An expansion module (zone, output) is offline.
    case TROUBLE_KEYPAD_OFFLINE = 'trouble_keypad_offline';       // A system keypad is not communicating.
    case TROUBLE_SYSTEM_DATE_TIME_INCORRECT = 'trouble_system_date_time_incorrect'; // Panel's internal clock is incorrect or needs setting.
    case TROUBLE_PRINTER_ISSUE_RECEIVER = 'trouble_printer_issue_receiver'; // Issue with printer connected to receiver (CSR context).

    // --- MAINTENANCE SIGNAL TYPES ---
    case MAINT_TECHNICIAN_LOGIN = 'maint_technician_login';     // Technician logged into the system locally or remotely for service.
    case MAINT_TECHNICIAN_LOGOUT = 'maint_technician_logout';    // Technician logged out after service.
    case MAINT_SERVICE_MODE_ENTERED = 'maint_service_mode_entered'; // System placed into a special service or maintenance mode.
    case MAINT_SERVICE_MODE_EXITED = 'maint_service_mode_exited'; // System taken out of service mode.

    // --- TEST SIGNAL TYPES ---
    case TEST_MANUAL_BY_USER_INSTALLER = 'test_manual_by_user_installer'; // Test initiated manually by user or installer.
    case TEST_PERIODIC_SYSTEM_AUTO = 'test_periodic_system_auto';  // Scheduled automatic system self-test.
    case TEST_WALK_MODE_ACTIVE = 'test_walk_mode_active';       // System is in walk-test mode for sensor verification.
    case TEST_COMMUNICATION_PATH = 'test_communication_path';   // Specific test of a communication path to the CSR.
    case TEST_BATTERY_CONDITION = 'test_battery_condition';     // System performed a test of its backup battery.
    case TEST_SIREN_BELL_OUTPUT = 'test_siren_bell_output';   // System performed a test of its siren/bell outputs.

    case TEST_COMMUNICATION = 'test_communication';

    // --- SUPERVISORY (CSR GENERATED - CLIENT SYSTEM FOCUSED) ---
    case SUPERVISORY_FAILURE_TO_ARM_SCHEDULE = 'supervisory_failure_to_arm_schedule'; // Site failed to arm by a predefined schedule.
    case SUPERVISORY_FAILURE_TO_DISARM_SCHEDULE = 'supervisory_failure_to_disarm_schedule'; // Site failed to disarm by a predefined schedule.
    case SUPERVISORY_COMM_TEST_MISSED = 'supervisory_comm_test_missed'; // Expected periodic communication test signal was not received.
    case SUPERVISORY_LATE_TO_OPEN_SCHEDULE = 'supervisory_late_to_open_schedule'; // Site not disarmed (opened) by expected business opening time.
    case SUPERVISORY_EARLY_TO_CLOSE_SCHEDULE = 'supervisory_early_to_close_schedule'; // Site armed (closed) before expected business closing time.
    case SUPERVISORY_UNEXPECTED_ACTIVITY = 'supervisory_unexpected_activity'; // Activity detected during normally closed/unoccupied hours.

    // --- SUPERVISORY (CSR GENERATED - CSR INFRASTRUCTURE FOCUSED) ---
    case CSR_INFRA_INTERNET_CONNECTIVITY_LOSS = 'csr_infra_internet_connectivity_loss'; // CSR backend lost primary internet connection.
    case CSR_INFRA_DATABASE_UNREACHABLE = 'csr_infra_database_unreachable';         // CSR backend cannot reach its primary database.
    case CSR_INFRA_RECEIVER_OFFLINE = 'csr_infra_receiver_offline';                 // A signal receiver component is offline.
    case CSR_INFRA_SERVICE_DEGRADATION = 'csr_infra_service_degradation';           // A critical CSR service is experiencing performance issues.

    // --- GENERIC / FALLBACK ---
    case GENERIC_ALARM_UNSPECIFIED = 'generic_alarm_unspecified';   // An alarm condition where the specific type is not known or provided.
    case GENERIC_TROUBLE_UNSPECIFIED = 'generic_trouble_unspecified'; // A trouble condition where the specific type is not known.
    case GENERIC_OPERATION_UNSPECIFIED = 'generic_operation_unspecified'; // A system operation where the specific type is not known.
    case UNKNOWN_EVENT_TYPE = 'unknown_event_type';                 // Event type could not be determined from the source data.

    /**
     * Provides a human-readable label for the event type.
     * This method would be very long; consider a helper service or localization for production.
     */
    public function label(): string
    {
        // For brevity, only a few examples. In a real app, this would be extensive or use a translation system.
        return match ($this) {
            self::BURGLARY_PERIMETER => 'Burglary - Perimeter Breach',
            self::OP_SYSTEM_ARM_AWAY => 'System Armed (Away Mode)',
            self::TROUBLE_AC_POWER_LOSS => 'AC Power Loss',
            self::SUPERVISORY_FAILURE_TO_ARM_SCHEDULE => 'Failure to Arm by Schedule',
            self::CSR_INFRA_INTERNET_CONNECTIVITY_LOSS => 'CSR Internet Connectivity Loss',
            self::UNKNOWN_EVENT_TYPE => 'Unknown Event Type',
            self::TEST_COMMUNICATION => 'Communication Test',
            // ... add all other cases
            default => str_replace('_', ' ', ucwords(strtolower($this->value), '_')), // Fallback to a formatted version of the case name
        };
    }
}
