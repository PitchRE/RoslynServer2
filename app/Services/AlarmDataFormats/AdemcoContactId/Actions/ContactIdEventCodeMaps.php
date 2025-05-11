<?php

// app/Services/AlarmDataFormats/AdemcoContactId/Data/ContactIdEventCodeMaps.php

namespace App\Services\AlarmDataFormats\AdemcoContactId\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventType;

class ContactIdEventCodeMaps
{
    // Constants for Contact ID Qualifiers
    public const QUALIFIER_NEW_EVENT = '1'; // E in the document

    public const QUALIFIER_RESTORAL_SECURE = '3'; // R in the document

    public const QUALIFIER_PREVIOUSLY_REPORTED = '6';

    protected static array $mappings = [];

    /**
     * Get the mapping for a specific EEE code.
     * Returns the base mapping which might be further processed by getEventMappingAndQualify.
     */
    public static function getMapping(string $eventCodeEee): ?array
    {
        if (empty(self::$mappings)) {
            self::initialize(); // Lazy load mappings
        }

        return self::$mappings[$eventCodeEee] ?? null;
    }

    /**
     * Get all defined mappings.
     */
    public static function getAllMappings(): array
    {
        if (empty(self::$mappings)) {
            self::initialize();
        }

        return self::$mappings;
    }

    /**
     * Initialize the event code mappings.
     * This is where the large array of Contact ID EEE code interpretations lives.
     */
    protected static function initialize(): void
    {
        self::$mappings = [
            // === Medical (Page 1) ===
            '100' => ['event_type' => SecurityEventType::MEDICAL_EMERGENCY_ASSISTANCE, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Medical Emergency', 'priority' => 5],
            '101' => ['event_type' => SecurityEventType::MEDICAL_PENDANT_ACTIVATION, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Pendant Transmitter Emergency', 'priority' => 5],
            '102' => ['event_type' => SecurityEventType::MEDICAL_FALL_DETECTED, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Fail to Report In (Medical Check-in)', 'priority' => 4],

            // === FIRE ALARMS (Page 1) ===
            '110' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Fire Alarm (Generic)', 'priority' => 5],
            '111' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Smoke Detected (w/Verification)', 'priority' => 5],
            '112' => ['event_type' => SecurityEventType::FIRE_HEAT_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Combustion Detected', 'priority' => 5],
            '113' => ['event_type' => SecurityEventType::FIRE_SPRINKLER_WATERFLOW, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Water Flow (Sprinkler)', 'priority' => 5],
            '114' => ['event_type' => SecurityEventType::FIRE_HEAT_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Heat Sensor Activation', 'priority' => 5],
            '115' => ['event_type' => SecurityEventType::FIRE_MANUAL_PULL_STATION, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Pull Station Activated', 'priority' => 5],
            '116' => ['event_type' => SecurityEventType::FIRE_DUCT_DETECTOR_ACTIVATION, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Duct Sensor Activated', 'priority' => 5],
            '117' => ['event_type' => SecurityEventType::FIRE_FLAME_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Flame Sensor Activated', 'priority' => 5],
            '118' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Near Alarm Condition (Fire Pre-Alarm)', 'priority' => 4],

            // === PANIC ALARMS (Page 1) ===
            '120' => ['event_type' => SecurityEventType::PANIC_HOLDUP_BUTTON, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Panic Alarm (Fixed Button)', 'priority' => 5],
            '121' => ['event_type' => SecurityEventType::PANIC_DURESS_CODE_USED, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Duress Alarm (User/Zone)', 'priority' => 5],
            '122' => ['event_type' => SecurityEventType::PANIC_SILENT_MANUAL, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Silent Panic', 'priority' => 5],
            '123' => ['event_type' => SecurityEventType::PANIC_AUDIBLE_MANUAL, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Audible Panic', 'priority' => 5],
            '124' => ['event_type' => SecurityEventType::PANIC_DURESS_CODE_USED, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Duress - Access Granted', 'priority' => 5],
            '125' => ['event_type' => SecurityEventType::PANIC_HOLDUP_BUTTON, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Duress - Egress Granted', 'priority' => 5],

            // === BURGLAR ALARMS (Page 1 & 2) ===
            '130' => ['event_type' => SecurityEventType::BURGLARY_INTERIOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Burglary (Generic)', 'priority' => 5],
            '131' => ['event_type' => SecurityEventType::BURGLARY_PERIMETER, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Perimeter Burglary', 'priority' => 5],
            '132' => ['event_type' => SecurityEventType::BURGLARY_INTERIOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Interior Burglary', 'priority' => 5],
            '133' => ['event_type' => SecurityEventType::BURGLARY_24_HOUR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => '24 Hour Burglary (Aux)', 'priority' => 5],
            '134' => ['event_type' => SecurityEventType::BURGLARY_DOOR_WINDOW_OPENED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Entry/Exit Zone Burglary', 'priority' => 5],
            '135' => ['event_type' => SecurityEventType::BURGLARY_MOTION_DETECTED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Day/Night Zone Burglary (Interior Follower)', 'priority' => 5],
            '136' => ['event_type' => SecurityEventType::BURGLARY_PERIMETER, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Outdoor Burglary', 'priority' => 4],
            '137' => ['event_type' => SecurityEventType::TAMPER_DEVICE_SENSOR, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Tamper (Sensor/Device)', 'priority' => 4],
            '138' => ['event_type' => SecurityEventType::BURGLARY_INTERIOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Near Alarm (Burglary Pre-Alarm)', 'priority' => 4],
            '139' => ['event_type' => SecurityEventType::BURGLARY_ASSET_PROTECTION, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Intrusion Verifier', 'priority' => 5],

            // === GENERAL ALARMS (Page 2) ===
            '140' => ['event_type' => SecurityEventType::GENERIC_ALARM_UNSPECIFIED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'General Alarm', 'priority' => 4],
            '141' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Polling Loop Open', 'priority' => 3],
            '142' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Polling Loop Short', 'priority' => 3],
            '143' => ['event_type' => SecurityEventType::TROUBLE_EXPANSION_MODULE_OFFLINE, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'Expansion Module Failure', 'priority' => 4],
            '144' => ['event_type' => SecurityEventType::TAMPER_DEVICE_SENSOR, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Sensor Tamper', 'priority' => 4],
            '145' => ['event_type' => SecurityEventType::TAMPER_CONTROL_PANEL, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Expansion Module Tamper', 'priority' => 4],
            '146' => ['event_type' => SecurityEventType::BURGLARY_SILENT_ALARM, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Silent Burglary', 'priority' => 5],
            '147' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Sensor Supervision Failure', 'priority' => 3],

            // === 24 HOUR NON-BURGLARY (Page 2) ===
            '150' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => '24 Hour Auxiliary Alarm (Generic Environmental)', 'priority' => 4],
            '151' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Gas Detected', 'priority' => 5],
            '152' => ['event_type' => SecurityEventType::ENV_REFRIGERATION_FAILURE, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Refrigeration Alarm', 'priority' => 4],
            '153' => ['event_type' => SecurityEventType::ENV_LOW_TEMPERATURE_LIMIT, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Loss of Heat (Heating System)', 'priority' => 4],
            '154' => ['event_type' => SecurityEventType::ENV_FLOOD_WATER_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Water Leakage', 'priority' => 4],
            '155' => ['event_type' => SecurityEventType::BURGLARY_FOIL_BREAK, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Foil Break (Window/Glass)', 'priority' => 5],
            '156' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Day Trouble (Zone Active During Disarmed)', 'priority' => 3],
            '157' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Low Bottled Gas Level', 'priority' => 3],
            '158' => ['event_type' => SecurityEventType::FIRE_CARBON_MONOXIDE, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Carbon Monoxide Detected', 'priority' => 5], // Or ALARM_FIRE if preferred for CO
            '159' => ['event_type' => SecurityEventType::ENV_LOW_TEMPERATURE_LIMIT, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Low Temperature', 'priority' => 4], // This is distinct from 153 Loss of Heat which implies system failure
            '161' => ['event_type' => SecurityEventType::ENV_HUMIDITY_OUT_OF_RANGE, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Loss of Air Flow (HVAC)', 'priority' => 3],
            '162' => ['event_type' => SecurityEventType::FIRE_CARBON_MONOXIDE, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Carbon Monoxide Detected', 'priority' => 5], // Doc lists 162 again for CO, this is the common one.
            '163' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Tank Level Trouble', 'priority' => 3],
            '168' => ['event_type' => SecurityEventType::ENV_HUMIDITY_OUT_OF_RANGE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'High Humidity Trouble', 'priority' => 3],
            '169' => ['event_type' => SecurityEventType::ENV_HUMIDITY_OUT_OF_RANGE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Low Humidity Trouble', 'priority' => 3],

            // === FIRE SUPERVISORY (Page 2) ===
            '200' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Fire Supervisory (Generic)', 'priority' => 3],
            '201' => ['event_type' => SecurityEventType::TROUBLE_SPRINKLER_PRESSURE_LOW, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Low Water Pressure (Sprinkler)', 'priority' => 3],
            '202' => ['event_type' => SecurityEventType::TROUBLE_CO2_LOW, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Low CO2 (Fire Suppression)', 'priority' => 3],
            '203' => ['event_type' => SecurityEventType::TROUBLE_GATE_VALVE_SENSOR, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Gate Valve Sensor Tamper/Off-Normal', 'priority' => 3],
            '204' => ['event_type' => SecurityEventType::TROUBLE_WATER_LEVEL_LOW, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Low Water Level (Fire Tank)', 'priority' => 3],
            '205' => ['event_type' => SecurityEventType::TROUBLE_PUMP_ACTIVATED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Pump Activated (Fire System)', 'priority' => 2],
            '206' => ['event_type' => SecurityEventType::TROUBLE_PUMP_FAILURE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Pump Failure (Fire System)', 'priority' => 3],

            // === SYSTEM TROUBLES (Page 2) ===
            '300' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'System Trouble (Generic)', 'priority' => 2],
            '301' => ['event_type' => SecurityEventType::TROUBLE_AC_POWER_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'AC Power Loss', 'priority' => 2],
            '302' => ['event_type' => SecurityEventType::TROUBLE_PANEL_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Low System Battery', 'priority' => 2],
            '303' => ['event_type' => SecurityEventType::TECHNICAL_MEMORY_CORRUPTION, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'RAM Checksum Bad', 'priority' => 3],
            '304' => ['event_type' => SecurityEventType::TECHNICAL_MEMORY_CORRUPTION, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'ROM Checksum Bad', 'priority' => 3],
            '305' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'System Reset', 'priority' => 1],
            '306' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_EXITED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Panel Programming Changed', 'priority' => 1],
            '307' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Self-Test Failure', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_FAIL],
            '308' => ['event_type' => SecurityEventType::TECHNICAL_SYSTEM_LOCKOUT, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'System Shutdown', 'priority' => 4],
            '309' => ['event_type' => SecurityEventType::TEST_BATTERY_CONDITION, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Battery Test Failure (at interval)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_FAIL],
            '310' => ['event_type' => SecurityEventType::TROUBLE_GROUND_FAULT_DETECTED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Ground Fault', 'priority' => 3],
            '311' => ['event_type' => SecurityEventType::TROUBLE_PANEL_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Battery Missing/Dead', 'priority' => 3],
            '312' => ['event_type' => SecurityEventType::TECHNICAL_POWER_SUPPLY_OVERLOAD, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'Power Supply Overcurrent', 'priority' => 3],
            '313' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Engineer Reset (User Specific)', 'priority' => 1],
            '314' => ['event_type' => SecurityEventType::TROUBLE_POWER_SUPPLY_PRIMARY_FAIL, 'category' => SecurityEventCategory::ALARM_TECHNICAL_CRITICAL, 'description' => 'Primary Power Supply Failure', 'priority' => 4],
            '316' => ['event_type' => SecurityEventType::TAMPER_CONTROL_PANEL, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'System Tamper (APL/Panel Box)', 'priority' => 4],

            // === SOUNDER/RELAY TROUBLES (Page 3) ===
            '320' => ['event_type' => SecurityEventType::TROUBLE_SIREN_BELL_CIRCUIT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Sounder/Relay Trouble (Generic)', 'priority' => 2],
            '321' => ['event_type' => SecurityEventType::TROUBLE_SIREN_BELL_CIRCUIT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Bell 1 / Siren 1 Trouble', 'priority' => 2],
            '322' => ['event_type' => SecurityEventType::TROUBLE_SIREN_BELL_CIRCUIT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Bell 2 / Siren 2 Trouble', 'priority' => 2],
            '323' => ['event_type' => SecurityEventType::TROUBLE_OUTPUT_RELAY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Alarm Relay Trouble', 'priority' => 2],
            '324' => ['event_type' => SecurityEventType::TROUBLE_OUTPUT_RELAY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Trouble Relay Trouble', 'priority' => 2],
            '325' => ['event_type' => SecurityEventType::TROUBLE_OUTPUT_RELAY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Reversing Relay Trouble', 'priority' => 2],
            '326' => ['event_type' => SecurityEventType::TROUBLE_NOTIFICATION_APPLIANCE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Notification Appliance Ckt #3 Trouble', 'priority' => 2],
            '327' => ['event_type' => SecurityEventType::TROUBLE_NOTIFICATION_APPLIANCE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Notification Appliance Ckt #4 Trouble', 'priority' => 2],

            // === SYSTEM PERIPHERAL TROUBLES (Page 3) ===
            '330' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'System Peripheral Trouble (LRR/ECP)', 'priority' => 2], // E355 in doc
            '331' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Polling Loop Open', 'priority' => 3],
            '332' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Polling Loop Short', 'priority' => 3],
            '333' => ['event_type' => SecurityEventType::TROUBLE_EXPANSION_MODULE_OFFLINE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Expansion Module Failure (ECP Path)', 'priority' => 3], // 353 in doc
            '334' => ['event_type' => SecurityEventType::TROUBLE_REPEATER_FAILURE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Repeater Failure', 'priority' => 3],
            '335' => ['event_type' => SecurityEventType::TROUBLE_PRINTER_ISSUE_RECEIVER, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Local Printer Paper Out', 'priority' => 1],
            '336' => ['event_type' => SecurityEventType::TROUBLE_PRINTER_ISSUE_RECEIVER, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Local Printer Failure', 'priority' => 1],
            '337' => ['event_type' => SecurityEventType::TROUBLE_EXPANSION_MODULE_POWER_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Expansion Module DC Loss', 'priority' => 2],
            '338' => ['event_type' => SecurityEventType::TROUBLE_EXPANSION_MODULE_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Expansion Module Low Battery', 'priority' => 2],
            '339' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Expansion Module Reset', 'priority' => 1],
            '341' => ['event_type' => SecurityEventType::TAMPER_CONTROL_PANEL, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Expansion Module Tamper', 'priority' => 3],
            '342' => ['event_type' => SecurityEventType::TROUBLE_AC_POWER_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Expansion Module AC Loss', 'priority' => 2],
            '343' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Expansion Module Self-Test Fail', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_FAIL],
            '344' => ['event_type' => SecurityEventType::TROUBLE_RF_JAMMING_DETECTED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'RF Receiver Jam Detected', 'priority' => 3],
            '345' => ['event_type' => SecurityEventType::TROUBLE_AES_ENCRYPTION, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'AES Encryption Disabled/Enabled Event', 'priority' => 2],

            // === COMMUNICATION TROUBLES (Page 3) ===
            '350' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Communication Trouble (Generic)', 'priority' => 3],
            '351' => ['event_type' => SecurityEventType::TROUBLE_PHONE_LINE_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Telco 1 Line Fault', 'priority' => 3],
            '352' => ['event_type' => SecurityEventType::TROUBLE_PHONE_LINE_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Telco 2 Line Fault', 'priority' => 3],
            '353' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Radio/Cellular Transmitter Fault (LRR)', 'priority' => 3], // Also 333
            '354' => ['event_type' => SecurityEventType::SUPERVISORY_COMM_TEST_MISSED, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Failure to Communicate Event to CSR', 'priority' => 4],
            'E355' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Loss of Radio Supervision (LRR/ECP)', 'priority' => 3], // R330 in doc
            '356' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Loss of Central Polling (Radio)', 'priority' => 3],
            '357' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'LRR Transmitter VSWR Trouble', 'priority' => 3],

            // === PROTECTION LOOP (Page 3) ===
            '370' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Protection Loop Trouble', 'priority' => 3],
            '371' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Protection Loop Open', 'priority' => 3],
            '372' => ['event_type' => SecurityEventType::TROUBLE_ZONE_WIRING_FAULT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Protection Loop Short', 'priority' => 3],
            '373' => ['event_type' => SecurityEventType::TAMPER_ZONE_WIRING, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Fire Loop Trouble (Supervision/Tamper/Ground)', 'priority' => 3],
            '374' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Exit Error by User (Alarm)', 'priority' => 4, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION], // Doc says Alarm-Exit Error
            '375' => ['event_type' => SecurityEventType::PANIC_SILENT_MANUAL, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Panic Zone Trouble', 'priority' => 3],
            '376' => ['event_type' => SecurityEventType::PANIC_HOLDUP_BUTTON, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Holdup Zone Trouble', 'priority' => 3],
            '377' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Swinger Trouble', 'priority' => 2],
            '378' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Cross-Zone Trouble', 'priority' => 2],

            // === SENSOR TROUBLES (Page 3 & 4) ===
            '380' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Global Sensor Trouble (Zone Types 5 & 19)', 'priority' => 2],
            '381' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'RF Sensor Supervision Loss', 'priority' => 3],
            '382' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_SUPERVISION_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'RPM Sensor Supervision Loss', 'priority' => 3],
            '383' => ['event_type' => SecurityEventType::TAMPER_DEVICE_SENSOR, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Sensor Tamper (Cover/Base)', 'priority' => 3],
            '384' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'RF Sensor Low Battery', 'priority' => 2],
            '385' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Smoke Detector High Sensitivity Trouble', 'priority' => 3],
            '386' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Smoke Detector Low Sensitivity Trouble', 'priority' => 3],
            '387' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Intrusion Detector High Sensitivity Trouble', 'priority' => 3], // -387 in doc
            '388' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Intrusion Detector Low Sensitivity Trouble', 'priority' => 3], // -388 in doc
            '389' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Detector Self-Test Failure', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_FAIL],
            '391' => ['event_type' => SecurityEventType::TROUBLE_SENSOR_WATCH_FAIL, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Sensor Watch Failure', 'priority' => 3],
            '392' => ['event_type' => SecurityEventType::TROUBLE_DRIFT_COMPENSATION_ERROR, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Drift Compensation Error (Smoke Detector)', 'priority' => 3],
            '393' => ['event_type' => SecurityEventType::MAINT_SERVICE_MODE_ENTERED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Maintenance Alert', 'priority' => 2],

            // === OPEN/CLOSE (Page 4) ===
            '400' => [
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_ACTIVITY_AUTO,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Open/Close (Scheduled/Keyswitch)',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_AUTO_SCHEDULE, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE],
            ],
            '401' => [
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_ACTIVITY_BY_USER,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Open/Close by User',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_BY_USER],
            ],
            '402' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_ACTIVITY_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Group Open/Close by User', 'priority' => 1], // Needs dynamic map like 401
            '403' => [
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_ACTIVITY_AUTO,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Automatic Open/Close',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_AUTO_SCHEDULE, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE],
            ],
            '404' => ['event_type' => SecurityEventType::SUPERVISORY_LATE_TO_OPEN_SCHEDULE, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Late to Open/Close', 'priority' => 2], // Needs Q logic
            '405' => ['event_type' => SecurityEventType::OP_SYSTEM_SCHEDULE_CHANGE, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Deferred Open/Close', 'priority' => 1], // Needs Q
            '406' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Cancel by User (Opening after alarm)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION],
            '407' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_ACTIVITY_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Remote Arm/Disarm (Keyswitch)', 'priority' => 1], // Needs dynamic map like 401
            '408' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_AWAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Quick Arm (Away - Closing)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '409' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_STAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Keyswitch Open/Close', 'priority' => 1], // This is Keyswitch O/C, needs dynamic mapping
            '435' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_GRANTED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Second Person Access (User)', 'priority' => 1],
            '436' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Irregular Access (User)', 'priority' => 2],
            '441' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_STAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Armed Stay (Closing)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '442' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_STAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Keyswitch Armed Stay (Closing)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '450' => ['event_type' => SecurityEventType::OP_USER_CODE_MANAGEMENT, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Exception Open/Close (User)', 'priority' => 1], // Needs Q
            '451' => ['event_type' => SecurityEventType::SUPERVISORY_EARLY_TO_CLOSE_SCHEDULE, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Early Open/Close by User', 'priority' => 2], // Needs Q
            '452' => ['event_type' => SecurityEventType::SUPERVISORY_LATE_TO_OPEN_SCHEDULE, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Late Open/Close by User', 'priority' => 2], // Needs Q
            '453' => ['event_type' => SecurityEventType::TROUBLE_SYSTEM_OPEN_FAIL, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Failed to Open', 'priority' => 3],
            '454' => ['event_type' => SecurityEventType::TROUBLE_SYSTEM_CLOSE_FAIL, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Failed to Close', 'priority' => 3],
            '455' => ['event_type' => SecurityEventType::SUPERVISORY_FAILURE_TO_ARM_SCHEDULE, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Auto-Arm Failed', 'priority' => 3, 'qualifier' => SecurityEventQualifier::SCHEDULE_VIOLATION],
            '456' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_STAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Partial Arm (User Closing)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '457' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Exit Error by User (Opening)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION],
            '458' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'User on Premises (Opening)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION],
            '459' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_BY_USER, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Recent Close by User', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION],
            '461' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Wrong Code Entry (Access/Arm)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_DENIED_INVALID],
            '462' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_GRANTED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Legal Code Entry (Access/Arm)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_AUTHORIZED_VALID],
            '463' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Re-arm after Alarm (User Closing)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '464' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Auto Arm Time Extended (User)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '465' => ['event_type' => SecurityEventType::PANIC_ALARM_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Panic Alarm Reset', 'priority' => 1],
            '466' => ['event_type' => SecurityEventType::MAINT_SERVICE_MODE_ENTERED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Service On/Off Premises (User)', 'priority' => 1], // Needs Q

            // === REMOTE ACCESS (Page 4) ===
            '411' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_START, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Callback Requested (Remote Access)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '412' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_END, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Successful Download/Access (Remote)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '413' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_START, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Unsuccessful Remote Access', 'priority' => 2, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '414' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'System Shutdown (Remote)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '415' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Dialer Shutdown (Remote)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '416' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_END, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Successful Upload (Remote)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],

            // === ACCESS CONTROL (Page 4 & 5) ===
            '421' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Denied (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_DENIED_INVALID],
            '422' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_GRANTED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Report by User (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_AUTHORIZED_VALID],
            '423' => ['event_type' => SecurityEventType::OP_DOOR_FORCED_OPEN_ALARM, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Forced Access (Panic Point#)', 'priority' => 4, 'qualifier' => SecurityEventQualifier::ACCESS_FORCED_TAMPER],
            '424' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Egress Denied (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_DENIED_INVALID],
            '425' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_GRANTED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Egress Granted (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_AUTHORIZED_VALID],
            '426' => ['event_type' => SecurityEventType::OP_DOOR_HELD_OPEN_ALARM, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Door Propped Open (Point#)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::ACCESS_DURATION_EXCEEDED],
            '427' => ['event_type' => SecurityEventType::TROUBLE_ACS_POINT_DSM, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Access Point DSM Trouble (Point#)', 'priority' => 2],
            '428' => ['event_type' => SecurityEventType::TROUBLE_ACS_POINT_RTE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Access Point RTE Trouble (Point#)', 'priority' => 2],
            '429' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_ENTERED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Program Mode Entry (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_ENTERED],
            '430' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_EXITED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Program Mode Exit (User#)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_EXITED],
            '431' => ['event_type' => SecurityEventType::OP_ACCESS_THREAT_LEVEL_CHANGE, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Threat Level Change', 'priority' => 2],
            '432' => ['event_type' => SecurityEventType::TROUBLE_ACS_RELAY_FAIL, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Access Relay/Trigger Fail (Point#)', 'priority' => 2],
            '433' => ['event_type' => SecurityEventType::OP_ACCESS_RTE_SHUNT, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access RTE Shunt (Point#)', 'priority' => 1],
            '434' => ['event_type' => SecurityEventType::OP_ACCESS_DSM_SHUNT, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access DSM Shunt (Point#)', 'priority' => 1],

            // === SYSTEM DISABLES (Page 5) ===
            '501' => ['event_type' => SecurityEventType::MAINT_ACCESS_READER_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Access Reader Disable (Point#)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],

            // === SOUNDER/RELAY DISABLES (Page 5) ===
            '520' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Sounder/Relay Disabled (Point#)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '521' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Bell 1 / Siren 1 Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '522' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Bell 2 / Siren 2 Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '523' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Alarm Relay Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '524' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Trouble Relay Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '525' => ['event_type' => SecurityEventType::MAINT_SOUNDER_RELAY_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Reversing Relay Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '526' => ['event_type' => SecurityEventType::MAINT_NOTIFICATION_APPLIANCE_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Notification Appliance Ckt #3 Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '527' => ['event_type' => SecurityEventType::MAINT_NOTIFICATION_APPLIANCE_DISABLE, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Notification Appliance Ckt #4 Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],

            // === SYSTEM PERIPHERAL DISABLES (Page 5) ===
            '531' => ['event_type' => SecurityEventType::MAINT_MODULE_ADDED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Module Added (Supervisory)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '532' => ['event_type' => SecurityEventType::MAINT_MODULE_REMOVED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Module Removed (Supervisory)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],

            // === COMMUNICATION DISABLES (Page 5) ===
            '551' => ['event_type' => SecurityEventType::MAINT_DIALER_DISABLED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Dialer Disabled', 'priority' => 3, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '552' => ['event_type' => SecurityEventType::MAINT_RADIO_XMITTER_DISABLED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Radio Transmitter Disabled', 'priority' => 3, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],
            '553' => ['event_type' => SecurityEventType::MAINT_REMOTE_ACCESS_DISABLED, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Remote Upload/Download Disabled', 'priority' => 2, 'qualifier' => SecurityEventQualifier::SYSTEM_AUTOMATED_ACTION],

            // === BYPASSES (Page 5) ===
            '570' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Zone/Sensor Bypass',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED],
            ],
            '571' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Fire Zone Bypass',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED], // Assuming general bypass/unbypass types
            ],
            '572' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => '24 Hour Zone Bypass',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED],
            ],
            '573' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Burglary Zone Bypass',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED],
            ],
            '574' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED,
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Group Bypass (User)',
                'priority' => 1,
                'qualifier_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED],
                'type_map' => [self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED, self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED],
            ],
            '575' => ['event_type' => SecurityEventType::OP_ZONE_BYPASSED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Swinger Bypass', 'priority' => 1, 'qualifier' => SecurityEventQualifier::BYPASS_ACTIVATED], // Typically only an activation
            '576' => ['event_type' => SecurityEventType::OP_ACCESS_ZONE_SHUNT, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Zone Shunt', 'priority' => 1], // Bypass type for ACS
            '577' => ['event_type' => SecurityEventType::OP_ACCESS_POINT_BYPASS, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Point Bypass', 'priority' => 1], // Bypass type for ACS
            '578' => ['event_type' => SecurityEventType::OP_ZONE_BYPASSED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Vault Zone Bypass', 'priority' => 1], // Needs dynamic map
            '579' => ['event_type' => SecurityEventType::OP_ZONE_BYPASSED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Vent Zone Bypass', 'priority' => 1], // Needs dynamic map

            // === TEST / MISC (Page 5) ===
            '601' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Manual Test', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_INITIATED],
            '602' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Periodic Test', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_PASS],
            '603' => ['event_type' => SecurityEventType::TEST_COMMUNICATION, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Periodic RF Transmission Test', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_PASS],
            '604' => ['event_type' => SecurityEventType::TEST_FIRE_WALK_TEST_USER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Fire Test (User Initiated)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_INITIATED],
            '605' => ['event_type' => SecurityEventType::TEST_FIRE_WALK_TEST_USER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Status Report To Follow (Fire Walk Test)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '606' => ['event_type' => SecurityEventType::AUDIO_VERIFICATION_INITIATED, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Listen-in to Follow', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '607' => ['event_type' => SecurityEventType::TEST_WALK_MODE_ACTIVE, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Walk Test Mode', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '608' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'System Trouble Present (During Test)', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '609' => ['event_type' => SecurityEventType::VIDEO_XMITTER_ACTIVE, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Video Transmitter Active', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '611' => ['event_type' => SecurityEventType::TEST_POINT_TESTED_OK, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Point Tested OK', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_PASS],
            '612' => ['event_type' => SecurityEventType::TEST_POINT_NOT_TESTED, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Point Not Tested', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_ABORTED], // Or TEST_COMPLETED_FAIL
            '613' => ['event_type' => SecurityEventType::TEST_INTRUSION_ZONE_WALK_TEST, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Intrusion Zone Walk Tested', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '614' => ['event_type' => SecurityEventType::TEST_FIRE_ZONE_WALK_TEST, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Fire Zone Walk Tested', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '615' => ['event_type' => SecurityEventType::TEST_PANIC_ZONE_WALK_TEST, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Panic Zone Walk Tested', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '616' => ['event_type' => SecurityEventType::MAINT_SERVICE_REQUEST, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'Service Request', 'priority' => 2],

            // === EVENT LOG (Page 5 & 6) ===
            '621' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Event Log Reset', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '622' => ['event_type' => SecurityEventType::INFORMATIONAL_LOG_EVENT_LOG_STATUS, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Event Log 50% Full', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '623' => ['event_type' => SecurityEventType::INFORMATIONAL_LOG_EVENT_LOG_STATUS, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Event Log 90% Full', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '624' => ['event_type' => SecurityEventType::INFORMATIONAL_LOG_EVENT_LOG_STATUS, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Event Log Overflow', 'priority' => 2, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '625' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Time/Date Reset by User', 'priority' => 1],
            '626' => ['event_type' => SecurityEventType::TROUBLE_SYSTEM_DATE_TIME_INCORRECT, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Time/Date Inaccurate (Clock requires stamping)', 'priority' => 1],
            '627' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_ENTERED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Program Mode Entry', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_ENTERED],
            '628' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_EXITED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Program Mode Exit', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_EXITED],

            // === SCHEDULING (Page 6) ===
            '630' => ['event_type' => SecurityEventType::OP_SYSTEM_SCHEDULE_CHANGE, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Schedule Change', 'priority' => 1],
            '631' => ['event_type' => SecurityEventType::OP_SYSTEM_SCHEDULE_CHANGE, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Exception Schedule Change', 'priority' => 1],
            '632' => ['event_type' => SecurityEventType::OP_SYSTEM_SCHEDULE_CHANGE, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Access Schedule Change', 'priority' => 1],

            // === PERSONNEL MONITORING (Page 6) ===
            '641' => ['event_type' => SecurityEventType::MEDICAL_FALL_DETECTED, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Senior Watch / Inactivity Trouble', 'priority' => 4], // Or a new "Inactivity" type
            '642' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Latch-key Supervision (User Arrived/Disarmed)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION],

            // === SPECIAL CODES (Page 6) ===
            '651' => ['event_type' => SecurityEventType::INFORMATIONAL_LOG_GENERIC, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'ADT Authorized Dealer Panel ID', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            // 750-789 are Protection One custom codes - map as GENERIC or UNKNOWN if not specifically handled by your system

            // === MISCELLANEOUS (Page 6) ===
            '654' => ['event_type' => SecurityEventType::SUPERVISORY_UNEXPECTED_ACTIVITY, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'System Inactivity (No Motion/Activity)', 'priority' => 3],
            '900' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_END, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Download Abort (Remote)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '901' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_START, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Download Start/End (Remote)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT], // Needs Q dynamic mapping
            '902' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_END, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Download Interrupted (Remote)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '910' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Auto-Close with Bypass', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '911' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Bypass Closing (User)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '912' => ['event_type' => SecurityEventType::FIRE_ALARM_SILENCED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Fire Alarm Silenced', 'priority' => 2],
            '913' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Supervisory Point Test Start/End (User#)', 'priority' => 1], // Needs Q
            '914' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Holdup Test Start/End (User#)', 'priority' => 1], // Needs Q
            '915' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Burglary Test Print Start/End', 'priority' => 1], // Needs Q
            '916' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Supervisory Test Print Start/End', 'priority' => 1], // Needs Q
            '917' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Burglary Diagnostics Start/End', 'priority' => 1], // Needs Q
            '918' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Fire Diagnostics Start/End', 'priority' => 1], // Needs Q
            '919' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Untyped Diagnostics Start/End', 'priority' => 1], // Needs Q
            '921' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Access Denied - Code Unknown', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_DENIED_INVALID],
            '922' => ['event_type' => SecurityEventType::GENERIC_ALARM_UNSPECIFIED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Supervisory Point Alarm (Zone#)', 'priority' => 3],
            '923' => ['event_type' => SecurityEventType::OP_ZONE_BYPASSED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Supervisory Point Bypass (Zone#)', 'priority' => 1], // Needs dynamic map
            '924' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Supervisory Point Trouble (Zone#)', 'priority' => 2],
            '925' => ['event_type' => SecurityEventType::OP_ZONE_BYPASSED, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Holdup Point Bypass (Zone#)', 'priority' => 1], // Needs dynamic map
            '926' => ['event_type' => SecurityEventType::TROUBLE_AC_POWER_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'AC Failure for 4 hours', 'priority' => 2],
            '927' => ['event_type' => SecurityEventType::TROUBLE_OUTPUT_RELAY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Output Trouble', 'priority' => 2],
            '928' => ['event_type' => SecurityEventType::OP_USER_CODE_MANAGEMENT, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'User Code for Event', 'priority' => 1], // Informational regarding a user action
            '929' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Log-off (System User/Tech)', 'priority' => 1],
            '954' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'CS Connection Failure (Panel to CS)', 'priority' => 4, 'qualifier' => SecurityEventQualifier::ACTIVATION],
            '961' => ['event_type' => SecurityEventType::CSR_INFRA_DATABASE_UNREACHABLE, 'category' => SecurityEventCategory::SUPERVISORY_CSR_INFRASTRUCTURE, 'description' => 'Receiver Database Connection Fail/Restore', 'priority' => 5], // Needs Q
            '962' => ['event_type' => SecurityEventType::MAINT_LICENSE_EXPIRY_NOTIFY, 'category' => SecurityEventCategory::MAINTENANCE_REQUIRED, 'description' => 'License Expiration Notify', 'priority' => 2], // Assuming new EventType

            // Other
            '999' => ['event_type' => SecurityEventType::INFORMATIONAL_LOG_GENERIC, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => '1 & 1/3 Day No Read Log (V20/lynx)', 'priority' => 1],

            // Fallback for truly unknown codes, though getMapping handles null return better
            '_UNKNOWN_' => ['event_type' => SecurityEventType::UNKNOWN_EVENT_TYPE, 'category' => SecurityEventCategory::UNCLASSIFIED_EVENT, 'description' => 'Unknown Contact ID Code', 'priority' => 3],
        ];

        // Mark dynamic mappings
        foreach (self::$mappings as $code => $mapping) {
            if (isset($mapping['qualifier_map']) || isset($mapping['type_map'])) {
                self::$mappings[$code]['_dynamic_mapping'] = true;
            }
        }
    }
}
