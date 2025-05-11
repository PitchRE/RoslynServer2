<?php

// app/Enums/SecurityEventType.php

namespace App\Enums;

enum SecurityEventType: string
{
    // --- MEDICAL ALARM TYPES ---
    case MEDICAL_EMERGENCY_ASSISTANCE = 'medical_emergency_assistance';   // General medical emergency request.
    case MEDICAL_PENDANT_ACTIVATION = 'medical_pendant_activation';       // Medical alarm from a wearable pendant.
    case MEDICAL_FALL_DETECTED = 'medical_fall_detected';               // Medical alarm triggered by a fall detector / Fail to report in.

    // --- FIRE ALARM TYPES ---
    case FIRE_SMOKE_DETECTOR = 'fire_smoke_detector';           // Smoke detector activation (can be generic for heat too).
    case FIRE_MANUAL_PULL_STATION = 'fire_manual_pull_station';   // Manual fire alarm pull station activated.
    case FIRE_SPRINKLER_WATERFLOW = 'fire_sprinkler_waterflow';   // Fire sprinkler system water flow detected.
    case FIRE_HEAT_DETECTOR = 'fire_heat_detector';             // Heat detector activation.
    case FIRE_DUCT_DETECTOR_ACTIVATION = 'fire_duct_detector_activation'; // Smoke/heat detected in HVAC duct.
    case FIRE_FLAME_DETECTOR = 'fire_flame_detector';           // Flame detector activation.
    case FIRE_CARBON_MONOXIDE = 'fire_carbon_monoxide';         // Carbon Monoxide (CO) detector activation (can also be environmental).
    case FIRE_ALARM_SILENCED = 'fire_alarm_silenced';           // Fire alarm has been silenced at the panel.

    // --- PANIC / DURESS ALARM TYPES ---
    case PANIC_HOLDUP_BUTTON = 'panic_holdup_button';         // Silent alarm indicating a robbery in progress, typically from a fixed button.
    case PANIC_DURESS_CODE_USED = 'panic_duress_code_used';     // User entered a special duress code.
    case PANIC_SILENT_MANUAL = 'panic_silent_manual';       // General silent panic alarm triggered manually.
    case PANIC_AUDIBLE_MANUAL = 'panic_audible_manual';      // General audible panic alarm triggered manually.
    case PANIC_KEYFOB_ACTIVATION = 'panic_keyfob_activation'; // Panic initiated from a wireless keyfob.
    case PANIC_AMBUSH = 'panic_ambush';                   // Ambush/Hostage situation reported.
    case PANIC_ALARM_RESET = 'panic_alarm_reset';           // Panic alarm has been reset at the panel.

    // --- BURGLARY ALARM TYPES ---
    case BURGLARY_PERIMETER = 'burglary_perimeter';       // Breach of external defenses (doors, windows).
    case BURGLARY_INTERIOR = 'burglary_interior';         // Intrusion detected within the protected area.
    case BURGLARY_24_HOUR = 'burglary_24_hour';           // Alarm from a zone that is always active (e.g., panic, safe).
    case BURGLARY_SILENT_ALARM = 'burglary_silent_alarm';     // Burglary alarm with no local sounder.
    case BURGLARY_AUDIBLE_ALARM = 'burglary_audible_alarm';    // Burglary alarm with local sounder.
    case BURGLARY_FOIL_BREAK = 'burglary_foil_break';       // Alarm from window foil protection being broken.
    case BURGLARY_GLASS_BREAK_DETECTOR = 'burglary_glass_break_detector'; // Alarm from acoustic glass break detector.
    case BURGLARY_MOTION_DETECTED = 'burglary_motion_detected'; // Alarm from a motion sensor.
    case BURGLARY_DOOR_WINDOW_OPENED = 'burglary_door_window_opened'; // Alarm from a door/window contact.
    case BURGLARY_SAFE_TAMPER_OPEN = 'burglary_safe_tamper_open'; // Alarm from a safe being tampered with or opened.
    case BURGLARY_ASSET_PROTECTION = 'burglary_asset_protection'; // Alarm from a sensor protecting a specific asset / Intrusion Verifier.

    // --- ENVIRONMENTAL HAZARD TYPES ---
    case ENV_GAS_LEAK_DETECTED = 'env_gas_leak_detected';           // Detection of a gas leak (natural, propane, etc.).
    case ENV_FLOOD_WATER_DETECTED = 'env_flood_water_detected';       // Water or flood detection.
    case ENV_HIGH_TEMPERATURE_LIMIT = 'env_high_temperature_limit';     // Temperature exceeds a critical upper limit.
    case ENV_LOW_TEMPERATURE_LIMIT = 'env_low_temperature_limit';      // Temperature falls below a critical lower limit / Loss of Heat.
    case ENV_HUMIDITY_OUT_OF_RANGE = 'env_humidity_out_of_range';     // Humidity level is too high or too low / Loss of Air Flow.
    case ENV_REFRIGERATION_FAILURE = 'env_refrigeration_failure';     // Specific alarm for refrigeration unit failure.

    // --- SYSTEM TAMPER TYPES ---
    case TAMPER_CONTROL_PANEL = 'tamper_control_panel';         // Control panel enclosure opened or tampered with / Expansion Module Tamper / APL System Tamper.
    case TAMPER_DEVICE_SENSOR = 'tamper_device_sensor';         // Sensor or peripheral device enclosure opened or tampered / RF Sensor Tamper.
    case TAMPER_ZONE_WIRING = 'tamper_zone_wiring';           // Tamper detected on a specific zone's wiring / Fire Loop Tamper.
    case TAMPER_KEYPAD = 'tamper_keypad';                   // Keypad tampered with.
    case TAMPER_SIREN_BELL = 'tamper_siren_bell';             // External sounder tampered with.

    // --- CRITICAL TECHNICAL ALARM TYPES ---
    case TECHNICAL_PANEL_CPU_FAILURE = 'technical_panel_cpu_failure'; // Critical CPU or mainboard failure in the panel.
    case TECHNICAL_MEMORY_CORRUPTION = 'technical_memory_corruption'; // Panel memory (RAM/ROM) corruption / Checksum Bad.
    case TECHNICAL_SYSTEM_LOCKOUT = 'technical_system_lockout';       // System locked out due to too many failed attempts or critical error / System Shutdown.
    case TECHNICAL_POWER_SUPPLY_OVERLOAD = 'technical_power_supply_overload'; // Panel power supply unit is overloaded.
    case TROUBLE_POWER_SUPPLY_PRIMARY_FAIL = 'trouble_power_supply_primary_fail'; // Primary Power Supply Failure (panel/communicator).

    // --- SYSTEM OPERATION / ACCESS TYPES ---

    case OP_SYSTEM_ARM_DISARM_ACTIVITY_BY_USER = 'op_system_arm_disarm_activity_by_user'; // Generic for CID 401 before Q is processed
    case OP_SYSTEM_ARM_DISARM_ACTIVITY_AUTO = 'op_system_arm_disarm_activity_auto';   // Generic for CID 400/403 before Q

    case OP_SYSTEM_ARM_AWAY = 'op_system_arm_away';               // System armed in "away" mode.
    case OP_SYSTEM_ARM_STAY = 'op_system_arm_stay';                 // System armed in "stay" mode.
    case OP_SYSTEM_ARM_INSTANT = 'op_system_arm_instant';           // System armed with no entry delay.
    case OP_SYSTEM_ARM_BY_USER = 'op_system_arm_by_user';           // System armed by a specific user code.
    case OP_SYSTEM_ARM_BY_KEYFOB = 'op_system_arm_by_keyfob';       // System armed via a wireless keyfob / Remote Arm.
    case OP_SYSTEM_ARM_AUTO_SCHEDULE = 'op_system_arm_auto_schedule'; // System armed automatically by a pre-set schedule.
    case OP_SYSTEM_DISARM_BY_USER = 'op_system_disarm_by_user';      // System disarmed by a specific user code / Cancel by User / Exit Error.
    case OP_SYSTEM_DISARM_BY_KEYFOB = 'op_system_disarm_by_keyfob';    // System disarmed via a wireless keyfob.
    case OP_SYSTEM_DISARM_AUTO_SCHEDULE = 'op_system_disarm_auto_schedule'; // System disarmed automatically by schedule.
    case OP_ZONE_BYPASSED = 'op_zone_bypassed';                   // A specific zone has been manually bypassed.
    case OP_ZONE_UNBYPASSED = 'op_zone_unbypassed';                 // A specific zone is no longer bypassed.
    case OP_SYSTEM_PROGRAMMING_ENTERED = 'op_system_programming_entered'; // Entered installer or user programming mode.
    case OP_SYSTEM_PROGRAMMING_EXITED = 'op_system_programming_exited';  // Exited programming mode / Program Changed.
    case OP_USER_CODE_MANAGEMENT = 'op_user_code_management';     // User code added, deleted, or changed / Exception O/C.
    case OP_SYSTEM_REBOOT_OR_RESET = 'op_system_reboot_or_reset';    // Control panel was rebooted or reset / Engineer Reset / Event Log Reset.
    case OP_DOOR_ACCESS_GRANTED = 'op_door_access_granted';       // Access permitted through a controlled door / Egress Granted.
    case OP_DOOR_ACCESS_DENIED = 'op_door_access_denied';         // Access attempted at a controlled door but denied / Wrong Code / Egress Denied.
    case OP_DOOR_FORCED_OPEN_ALARM = 'op_door_forced_open_alarm';    // Controlled door was forced open.
    case OP_DOOR_HELD_OPEN_ALARM = 'op_door_held_open_alarm';      // Controlled door was held open too long.
    case OP_SYSTEM_OUTPUT_ACTIVATED = 'op_system_output_activated';   // A programmable output on the panel was activated.
    case OP_SYSTEM_OUTPUT_DEACTIVATED = 'op_system_output_deactivated'; // A programmable output was deactivated.
    case OP_REMOTE_SESSION_START = 'op_remote_session_start';     // Remote access/programming session started / Callback Requested / Download Start.
    case OP_REMOTE_SESSION_END = 'op_remote_session_end';       // Remote access/programming session ended / Download Success/Abort/Interrupt / Successful Upload.
    case OP_SYSTEM_SCHEDULE_CHANGE = 'op_system_schedule_change';   // System schedule has been changed (generic, access, exception).
    case OP_ACCESS_THREAT_LEVEL_CHANGE = 'op_access_threat_level_change'; // Access control system threat level changed.
    case OP_ACCESS_RTE_SHUNT = 'op_access_rte_shunt';               // Access control Request-to-Exit shunted.
    case OP_ACCESS_DSM_SHUNT = 'op_access_dsm_shunt';               // Access control Door Status Monitor shunted.
    case OP_ACCESS_ZONE_SHUNT = 'op_access_zone_shunt';             // Access control zone shunted/bypassed.
    case OP_ACCESS_POINT_BYPASS = 'op_access_point_bypass';         // Access control point bypassed.

    // --- SYSTEM TROUBLE (NON-CRITICAL) TYPES ---
    case TROUBLE_AC_POWER_LOSS = 'trouble_ac_power_loss';             // Loss of main AC power to the panel / Expansion Module AC Loss.
    case TROUBLE_PANEL_LOW_BATTERY = 'trouble_panel_low_battery';       // Control panel backup battery is low / Missing/Dead.
    case TROUBLE_DEVICE_LOW_BATTERY = 'trouble_device_low_battery';    // Wireless sensor or peripheral device battery is low / RF Sensor Low Battery.
    case TROUBLE_COMM_PATH_PRIMARY = 'trouble_comm_path_primary';     // Failure on primary comm path (Telco, IP, Cell, Radio) / LRR Xmitter Fault / VSWR.
    case TROUBLE_PHONE_LINE_FAULT = 'trouble_phone_line_fault';        // Fault detected on the POTS phone line (Telco 1 or 2).
    case TROUBLE_RF_JAMMING_DETECTED = 'trouble_rf_jamming_detected';    // Radio frequency jamming detected.
    case TROUBLE_DEVICE_SUPERVISION_LOSS = 'trouble_device_supervision_loss'; // Wireless device missing or not responding / RF or RPM Sensor Supervision / Global Sensor Trouble / LRR Supervision.
    case TROUBLE_ZONE_WIRING_FAULT = 'trouble_zone_wiring_fault';      // Wiring fault (open, short) on a specific hardwired zone / Polling Loop / Protection Loop / Fire Loop Ground.
    case TROUBLE_PANEL_FUSE_BLOWN = 'trouble_panel_fuse_blown';        // A fuse within the control panel or power supply has blown.
    case TROUBLE_GROUND_FAULT_DETECTED = 'trouble_ground_fault_detected'; // Ground fault detected in the system wiring.
    case TROUBLE_SIREN_BELL_CIRCUIT = 'trouble_siren_bell_circuit';    // Trouble with the siren or bell output circuit / Sounder/Relay Trouble.
    case TROUBLE_EXPANSION_MODULE_OFFLINE = 'trouble_expansion_module_offline'; // An expansion module is offline / Failure.
    case TROUBLE_KEYPAD_OFFLINE = 'trouble_keypad_offline';           // A system keypad is not communicating.
    case TROUBLE_SYSTEM_DATE_TIME_INCORRECT = 'trouble_system_date_time_incorrect'; // Panel's internal clock is incorrect or needs setting.
    case TROUBLE_PRINTER_ISSUE_RECEIVER = 'trouble_printer_issue_receiver'; // Issue with printer connected to receiver (CSR context) / Paper Out / Failure.
    case TROUBLE_OUTPUT_RELAY = 'trouble_output_relay';             // Trouble with Alarm, Trouble, or Reversing Relay.
    case TROUBLE_NOTIFICATION_APPLIANCE = 'trouble_notification_appliance'; // Trouble with Notification Appliance Circuit.
    case TROUBLE_REPEATER_FAILURE = 'trouble_repeater_failure';       // Wireless repeater failure.
    case TROUBLE_EXPANSION_MODULE_POWER_LOSS = 'trouble_expansion_module_power_loss'; // DC Power Loss to expansion module.
    case TROUBLE_EXPANSION_MODULE_LOW_BATTERY = 'trouble_expansion_module_low_battery'; // Low battery on expansion module.
    case TROUBLE_AES_ENCRYPTION = 'trouble_aes_encryption';           // AES Encryption status change or trouble.
    case TROUBLE_SENSOR_WATCH_FAIL = 'trouble_sensor_watch_fail';     // Sensor watch feature failed.
    case TROUBLE_DRIFT_COMPENSATION_ERROR = 'trouble_drift_compensation_error'; // Smoke detector drift compensation error.
    case TROUBLE_SPRINKLER_PRESSURE_LOW = 'trouble_sprinkler_pressure_low'; // Low Water Pressure (Sprinkler Supervisory).
    case TROUBLE_CO2_LOW = 'trouble_co2_low';                       // Low CO2 (Fire Suppression Supervisory).
    case TROUBLE_GATE_VALVE_SENSOR = 'trouble_gate_valve_sensor';     // Gate Valve Sensor issue (Fire Supervisory).
    case TROUBLE_WATER_LEVEL_LOW = 'trouble_water_level_low';         // Low Water Level (Fire Tank Supervisory).
    case TROUBLE_PUMP_ACTIVATED = 'trouble_pump_activated';           // Pump Activated (Fire Supervisory - might be informational).
    case TROUBLE_PUMP_FAILURE = 'trouble_pump_failure';             // Pump Failure (Fire Supervisory).
    case TROUBLE_SYSTEM_OPEN_FAIL = 'trouble_system_open_fail';       // Panel failed to disarm/open as expected.
    case TROUBLE_SYSTEM_CLOSE_FAIL = 'trouble_system_close_fail';      // Panel failed to arm/close as expected.
    case TROUBLE_ACS_POINT_DSM = 'trouble_acs_point_dsm';             // Access Control Point DSM Trouble.
    case TROUBLE_ACS_POINT_RTE = 'trouble_acs_point_rte';             // Access Control Point RTE Trouble.
    case TROUBLE_ACS_RELAY_FAIL = 'trouble_acs_relay_fail';           // Access Control Relay/Trigger Fail.

    // --- MAINTENANCE SIGNAL TYPES ---
    case MAINT_TECHNICIAN_LOGIN = 'maint_technician_login';         // Technician logged into the system.
    case MAINT_TECHNICIAN_LOGOUT = 'maint_technician_logout';        // Technician logged out.
    case MAINT_SERVICE_MODE_ENTERED = 'maint_service_mode_entered';    // System placed into service mode / Maintenance Alert / Event Log 75% Full (as per doc).
    case MAINT_SERVICE_MODE_EXITED = 'maint_service_mode_exited';     // System taken out of service mode.
    case MAINT_ACCESS_READER_DISABLE = 'maint_access_reader_disable'; // Access Reader Disabled for maintenance.
    case MAINT_SOUNDER_RELAY_DISABLE = 'maint_sounder_relay_disable'; // Sounder/Relay/Bell/Appliance Circuit Disabled.
    case MAINT_NOTIFICATION_APPLIANCE_DISABLE = 'maint_notification_appliance_disable'; // Notification Appliance Ckt Disabled.
    case MAINT_MODULE_ADDED = 'maint_module_added';                 // System Module Added (Supervisory).
    case MAINT_MODULE_REMOVED = 'maint_module_removed';               // System Module Removed (Supervisory).
    case MAINT_DIALER_DISABLED = 'maint_dialer_disabled';             // Dialer Disabled for maintenance.
    case MAINT_RADIO_XMITTER_DISABLED = 'maint_radio_xmitter_disabled'; // Radio Transmitter Disabled.
    case MAINT_REMOTE_ACCESS_DISABLED = 'maint_remote_access_disabled'; // Remote Upload/Download Disabled.
    case MAINT_SERVICE_REQUEST = 'maint_service_request';             // Panel or system is requesting service.

    // --- TEST SIGNAL TYPES ---
    case TEST_MANUAL_BY_USER_INSTALLER = 'test_manual_by_user_installer'; // Test initiated manually by user or installer.
    case TEST_PERIODIC_SYSTEM_AUTO = 'test_periodic_system_auto';  // Scheduled automatic system self-test / Detector Self-Test.
    case TEST_WALK_MODE_ACTIVE = 'test_walk_mode_active';           // System is in walk-test mode for sensor verification.
    case TEST_COMMUNICATION = 'test_communication';               // Specific test of a communication path / Periodic RF Xmission.
    case TEST_BATTERY_CONDITION = 'test_battery_condition';         // System performed a test of its backup battery.
    case TEST_SIREN_BELL_OUTPUT = 'test_siren_bell_output';       // System performed a test of its siren/bell outputs.
    case TEST_FIRE_WALK_TEST_USER = 'test_fire_walk_test_user';     // Fire Walk Test initiated by user.
    case VIDEO_XMITTER_ACTIVE = 'video_xmitter_active';           // Video transmitter is active (often during test/event).
    case TEST_POINT_TESTED_OK = 'test_point_tested_ok';           // Specific point/sensor tested OK.
    case TEST_POINT_NOT_TESTED = 'test_point_not_tested';         // Specific point/sensor was not tested during a sequence.
    case TEST_INTRUSION_ZONE_WALK_TEST = 'test_intrusion_zone_walk_test'; // Intrusion zone walk test event.
    case TEST_FIRE_ZONE_WALK_TEST = 'test_fire_zone_walk_test';     // Fire zone walk test event.
    case TEST_PANIC_ZONE_WALK_TEST = 'test_panic_zone_walk_test';   // Panic zone walk test event.
    case AUDIO_VERIFICATION_INITIATED = 'audio_verification_initiated'; // Listen-in to Follow / Audio Verification sequence started.

    // --- SUPERVISORY (CSR GENERATED - CLIENT SYSTEM FOCUSED) ---
    case SUPERVISORY_FAILURE_TO_ARM_SCHEDULE = 'supervisory_failure_to_arm_schedule'; // Site failed to arm by predefined schedule / Auto-Arm Failed.
    case SUPERVISORY_FAILURE_TO_DISARM_SCHEDULE = 'supervisory_failure_to_disarm_schedule'; // Site failed to disarm by predefined schedule.
    case SUPERVISORY_COMM_TEST_MISSED = 'supervisory_comm_test_missed'; // Expected periodic communication test signal was not received.
    case SUPERVISORY_LATE_TO_OPEN_SCHEDULE = 'supervisory_late_to_open_schedule'; // Site not disarmed (opened) by expected business opening time.
    case SUPERVISORY_EARLY_TO_CLOSE_SCHEDULE = 'supervisory_early_to_close_schedule'; // Site armed (closed) before expected business closing time.
    case SUPERVISORY_UNEXPECTED_ACTIVITY = 'supervisory_unexpected_activity'; // Activity detected during normally closed/unoccupied hours / System Inactivity.

    // --- SUPERVISORY (CSR GENERATED - CSR INFRASTRUCTURE FOCUSED) ---
    case CSR_INFRA_INTERNET_CONNECTIVITY_LOSS = 'csr_infra_internet_connectivity_loss';
    case CSR_INFRA_DATABASE_UNREACHABLE = 'csr_infra_database_unreachable';
    case CSR_INFRA_RECEIVER_OFFLINE = 'csr_infra_receiver_offline';
    case CSR_INFRA_SERVICE_DEGRADATION = 'csr_infra_service_degradation';

    // --- INFORMATIONAL & GENERIC LOGGING ---
    case INFORMATIONAL_LOG_GENERIC = 'informational_log_generic';       // For generic informational logs not fitting elsewhere (e.g., Log Full, ADT Dealer ID).
    case INFORMATIONAL_LOG_EVENT_LOG_STATUS = 'informational_log_event_log_status'; // For Event Log 50% Full, 90% Full, Overflow.

    // --- GENERIC / FALLBACK ---
    case GENERIC_ALARM_UNSPECIFIED = 'generic_alarm_unspecified';       // An alarm condition where the specific type is not known or provided.
    case GENERIC_TROUBLE_UNSPECIFIED = 'generic_trouble_unspecified';    // A trouble condition where the specific type is not known.
    case GENERIC_OPERATION_UNSPECIFIED = 'generic_operation_unspecified'; // A system operation where the specific type is not known.
    case UNKNOWN_EVENT_TYPE = 'unknown_event_type';                     // Event type could not be determined from the source data.

    case MAINT_LICENSE_EXPIRY_NOTIFY = 'maint_license_expiry_notify';

    public function label(): string
    {
        // This will be a very long match statement.
        // Consider using a helper or localization for maintainability.
        return match ($this) {
            self::MEDICAL_EMERGENCY_ASSISTANCE => 'Medical Emergency Assistance',
            self::MEDICAL_PENDANT_ACTIVATION => 'Medical Pendant Activation',
            self::MEDICAL_FALL_DETECTED => 'Medical Fall Detected / Fail to Report In',
            self::FIRE_SMOKE_DETECTOR => 'Fire - Smoke/Heat Detector',
            self::FIRE_MANUAL_PULL_STATION => 'Fire - Manual Pull Station',
            self::FIRE_SPRINKLER_WATERFLOW => 'Fire - Sprinkler Waterflow',
            // ... Add labels for ALL cases ...
            self::TROUBLE_SPRINKLER_PRESSURE_LOW => 'Trouble - Sprinkler Low Water Pressure',
            self::TROUBLE_CO2_LOW => 'Trouble - Low CO2 (Suppression)',
            self::TROUBLE_GATE_VALVE_SENSOR => 'Trouble - Gate Valve Sensor',
            self::TROUBLE_WATER_LEVEL_LOW => 'Trouble - Low Water Level (Fire Tank)',
            self::TROUBLE_PUMP_ACTIVATED => 'Trouble - Fire Pump Activated',
            self::TROUBLE_PUMP_FAILURE => 'Trouble - Fire Pump Failure',
            self::TROUBLE_POWER_SUPPLY_PRIMARY_FAIL => 'Trouble - Primary Power Supply Failure',
            self::TROUBLE_SYSTEM_OPEN_FAIL => 'Trouble - System Failed to Open',
            self::TROUBLE_SYSTEM_CLOSE_FAIL => 'Trouble - System Failed to Close',
            self::AUDIO_VERIFICATION_INITIATED => 'Audio Verification Initiated',
            self::INFORMATIONAL_LOG_EVENT_LOG_STATUS => 'Informational - Event Log Status',
            self::OP_SYSTEM_SCHEDULE_CHANGE => 'Operation - System Schedule Change',
            self::TROUBLE_OUTPUT_RELAY => 'Trouble - Output Relay',
            self::TROUBLE_NOTIFICATION_APPLIANCE => 'Trouble - Notification Appliance',
            self::TROUBLE_REPEATER_FAILURE => 'Trouble - Repeater Failure',
            self::TROUBLE_EXPANSION_MODULE_POWER_LOSS => 'Trouble - Expansion Module Power Loss',
            self::TROUBLE_EXPANSION_MODULE_LOW_BATTERY => 'Trouble - Expansion Module Low Battery',
            self::TROUBLE_AES_ENCRYPTION => 'Trouble - AES Encryption',
            self::TROUBLE_SENSOR_WATCH_FAIL => 'Trouble - Sensor Watch Fail',
            self::TROUBLE_DRIFT_COMPENSATION_ERROR => 'Trouble - Drift Compensation Error',
            self::PANIC_ALARM_RESET => 'Panic Alarm Reset',
            self::OP_ACCESS_THREAT_LEVEL_CHANGE => 'Operation - Access Threat Level Change',
            self::TROUBLE_ACS_RELAY_FAIL => 'Trouble - ACS Relay Fail',
            self::OP_ACCESS_RTE_SHUNT => 'Operation - Access RTE Shunt',
            self::OP_ACCESS_DSM_SHUNT => 'Operation - Access DSM Shunt',
            self::MAINT_ACCESS_READER_DISABLE => 'Maintenance - Access Reader Disable',
            self::MAINT_SOUNDER_RELAY_DISABLE => 'Maintenance - Sounder/Relay Disable',
            self::MAINT_NOTIFICATION_APPLIANCE_DISABLE => 'Maintenance - Notification Appliance Disable',
            self::MAINT_MODULE_ADDED => 'Maintenance - Module Added',
            self::MAINT_MODULE_REMOVED => 'Maintenance - Module Removed',
            self::MAINT_DIALER_DISABLED => 'Maintenance - Dialer Disabled',
            self::MAINT_RADIO_XMITTER_DISABLED => 'Maintenance - Radio Xmitter Disabled',
            self::MAINT_REMOTE_ACCESS_DISABLED => 'Maintenance - Remote Access Disabled',
            self::OP_ACCESS_ZONE_SHUNT => 'Operation - Access Zone Shunt',
            self::OP_ACCESS_POINT_BYPASS => 'Operation - Access Point Bypass',
            self::TEST_FIRE_WALK_TEST_USER => 'Test - Fire Walk Test (User)',
            self::VIDEO_XMITTER_ACTIVE => 'Video Transmitter Active',
            self::TEST_POINT_TESTED_OK => 'Test - Point Tested OK',
            self::TEST_POINT_NOT_TESTED => 'Test - Point Not Tested',
            self::TEST_INTRUSION_ZONE_WALK_TEST => 'Test - Intrusion Zone Walk Test',
            self::TEST_FIRE_ZONE_WALK_TEST => 'Test - Fire Zone Walk Test',
            self::TEST_PANIC_ZONE_WALK_TEST => 'Test - Panic Zone Walk Test',
            self::MAINT_SERVICE_REQUEST => 'Maintenance - Service Request',
            self::INFORMATIONAL_LOG_GENERIC => 'Informational Log',

            self::OP_SYSTEM_ARM_DISARM_ACTIVITY_BY_USER => 'System Arm/Disarm Activity by User',
            self::OP_SYSTEM_ARM_DISARM_ACTIVITY_AUTO => 'System Arm/Disarm Activity (Auto/Scheduled)',

            self::MAINT_LICENSE_EXPIRY_NOTIFY => 'License Expiration Notify',

            default => str_replace(['_', '-'], ' ', ucwords(strtolower($this->value), '_-')),
        };
    }
}
