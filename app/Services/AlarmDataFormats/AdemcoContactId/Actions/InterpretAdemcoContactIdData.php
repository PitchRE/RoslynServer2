<?php

namespace App\Services\AlarmDataFormats\AdemcoContactId\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use App\Models\Device;
use App\Models\Partition;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\User; // Assuming this is your Panel User model
use App\Models\Zone;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction; // If you use UUIDs for SecurityEvent ID

class InterpretAdemcoContactIdData
{
    use AsAction;

    protected const QUALIFIER_NEW_EVENT = '1';

    protected const QUALIFIER_RESTORAL_SECURE = '3';

    protected const QUALIFIER_PREVIOUSLY_REPORTED = '6';

    protected array $eventCodeMappings = [];

    /**
     * Interpret the raw Ademco Contact ID data and return a new SecurityEvent model instance.
     * The model instance will be new and not yet persisted.
     *
     * @param  int|null  $deviceId  The ID of the panel/device sending the signal.
     * @param  string  $rawContactIdCode  The full Contact ID string.
     * @param  CarbonImmutable|null  $occurredAt  Timestamp if provided by receiver.
     * @return SecurityEvent|null Returns a new SecurityEvent model or null on parsing failure.
     */
    public function handle(?int $deviceId, string $rawContactIdCode, ?CarbonImmutable $occurredAt = null): ?SecurityEvent
    {
        $this->initializeEventCodeMappings();

        $parsedData = $this->parseContactIdString($rawContactIdCode);

        if (! $parsedData) {
            Log::warning("Failed to parse Contact ID string: {$rawContactIdCode} for device ID: {$deviceId}");

            return null;
        }

        $mapping = $this->getEventMappingAndQualify($parsedData['qualifier_q'], $parsedData['event_code_eee']);

        $eventType = $mapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $eventCategory = $mapping['category'] ?? SecurityEventCategory::UNCLASSIFIED_EVENT;
        $eventQualifier = $mapping['qualifier'] ?? $this->determineEventQualifier($parsedData['qualifier_q'], $eventType, $mapping);
        $priority = $mapping['priority'] ?? 3; // Default priority (e.g., Medium)
        $rawDescription = $mapping['description'] ?? "Contact ID Event: {$parsedData['event_code_eee']}";

        $rawZoneIdentifier = null;
        $rawUserIdentifier = null;
        $identifierType = $this->isZoneOrUserCode($parsedData['event_code_eee']);

        if ($identifierType === 'zone' && $parsedData['zone_user_zzz'] !== '000') {
            $rawZoneIdentifier = $parsedData['zone_user_zzz'];
        } elseif ($identifierType === 'user' && $parsedData['zone_user_zzz'] !== '000') {
            $rawUserIdentifier = $parsedData['zone_user_zzz'];
        }

        $deviceModel = $deviceId ? Device::find($deviceId) : null;
        $siteModel = null;
        if ($deviceModel && $deviceModel->site_id) { // Assuming Device has a site_id
            $siteModel = Site::find($deviceModel->site_id);
        } elseif ($parsedData['account_aaaa']) {
            // Fallback to lookup site by account number if device or its site link is not found
            $siteModel = Site::where('account_number', $parsedData['account_aaaa'])->first();
        }

        $securityEvent = new SecurityEvent;
        // If your SecurityEvent model uses UUIDs and doesn't auto-generate them on creating:
        // $securityEvent->id = (string) Str::uuid();

        $securityEvent->occurred_at = $occurredAt ?? Carbon::now();
        $securityEvent->received_at = Carbon::now();
        // processed_at can be set upon saving or by a subsequent process

        $securityEvent->source_protocol = 'CONTACT_ID'; // Or use an Enum if you have one for protocols
        $securityEvent->raw_event_code = $parsedData['qualifier_q'].$parsedData['event_code_eee'];
        $securityEvent->raw_event_description = $rawDescription;

        $securityEvent->site_id = $siteModel?->id;
        $securityEvent->raw_account_identifier = $parsedData['account_aaaa'];

        $securityEvent->device_id = $deviceModel?->id;
        $securityEvent->raw_device_identifier = $deviceModel?->identifier ?? ($deviceId ? (string) $deviceId : null); // Assuming Device has an 'identifier' field

        $securityEvent->raw_partition_identifier = $parsedData['partition_gg'] === '00' ? null : $parsedData['partition_gg'];
        if ($siteModel && $securityEvent->raw_partition_identifier) {
            $partitionModel = Partition::where('site_id', $siteModel->id)
                ->where('partition_number', $securityEvent->raw_partition_identifier) // Assuming 'partition_number' column
                ->first();
            $securityEvent->partition_id = $partitionModel?->id;
        }

        $securityEvent->raw_zone_identifier = $rawZoneIdentifier;
        if ($siteModel && $rawZoneIdentifier) {
            $zoneQuery = Zone::where('site_id', $siteModel->id)
                ->where('zone_number', $rawZoneIdentifier); // Assuming 'zone_number' column
            if ($securityEvent->partition_id) { // If partition is known, scope zone to partition
                $zoneQuery->where('partition_id', $securityEvent->partition_id);
            }
            $zoneModel = $zoneQuery->first();
            $securityEvent->zone_id = $zoneModel?->id;
        }

        $securityEvent->raw_user_identifier = $rawUserIdentifier;
        if ($siteModel && $rawUserIdentifier) {
            $panelUserModel = User::where('site_id', $siteModel->id) // Assuming User model is for panel users
                ->where('panel_user_code', $rawUserIdentifier) // Assuming 'panel_user_code' column
                ->first();
            $securityEvent->user_id = $panelUserModel?->id; // This is panel_user_id in SecurityEvent
        }

        $securityEvent->event_category = $eventCategory;
        $securityEvent->event_type = $eventType;
        $securityEvent->event_qualifier = $eventQualifier;
        $securityEvent->priority = $priority;

        $descParts = ["Contact ID: {$rawDescription}"];
        if ($siteModel) {
            $descParts[] = "Site: {$siteModel->name} ({$parsedData['account_aaaa']})";
        } else {
            $descParts[] = "Acct: {$parsedData['account_aaaa']}";
        }

        // Attempt to get resolved names for partition, zone, user
        $partitionInstance = $securityEvent->partition_id ? Partition::find($securityEvent->partition_id) : null;
        $zoneInstance = $securityEvent->zone_id ? Zone::find($securityEvent->zone_id) : null;
        $panelUserInstance = $securityEvent->user_id ? User::find($securityEvent->user_id) : null;

        if ($securityEvent->raw_partition_identifier) {
            $partitionName = $partitionInstance?->name ?? $securityEvent->raw_partition_identifier;
            $descParts[] = "Part: {$partitionName}";
        }
        if ($rawZoneIdentifier) {
            $zoneName = $zoneInstance?->name ?? $rawZoneIdentifier;
            $descParts[] = "Zone: {$zoneName}";
        }
        if ($rawUserIdentifier) {
            $userName = $panelUserInstance?->name ?? $rawUserIdentifier; // Assuming User model has a 'name'
            $descParts[] = "User: {$userName}";
        }
        $securityEvent->normalized_description = implode(' - ', $descParts);

        $securityEvent->message_details = json_encode($parsedData);
        $securityEvent->status = SecurityEventStatus::NEW;

        return $securityEvent;
    }

    protected function parseContactIdString(string $rawContactIdCode): ?array
    {
        $cleanedCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($rawContactIdCode));

        if (preg_match('/^([0-9A-F]{3,6})([136])([0-9A-F]{3})(?:([0-9A-F]{2})(?:([0-9A-F]{3}))?)?$/', $cleanedCode, $matches)) {
            $account = $matches[1];
            $qualifier = $matches[2];
            $eventCode = $matches[3];
            $partition = $matches[4] ?? '00';
            $zoneUser = $matches[5] ?? '000';

            // Pad account to 4 digits if shorter for consistency, if it's purely numeric
            if (strlen($account) < 4 && ctype_digit($account)) {
                $account = str_pad($account, 4, '0', STR_PAD_LEFT);
            }

            return [
                'account_aaaa' => $account,
                'qualifier_q' => $qualifier,
                'event_code_eee' => $eventCode,
                'partition_gg' => $partition,
                'zone_user_zzz' => $zoneUser,
            ];
        }

        Log::debug("Contact ID regex did not match for code: {$cleanedCode}");

        return null;
    }

    protected function determineEventQualifier(string $qualifierQ, SecurityEventType $eventType, array $mapping): SecurityEventQualifier
    {
        // If the mapping directly provided a qualifier (e.g., from qualifier_map), it would have been set already.
        // This method acts as a general fallback based on Q if no specific qualifier was derived from the mapping logic.
        if (isset($mapping['qualifier']) && $mapping['qualifier'] instanceof SecurityEventQualifier) {
            return $mapping['qualifier'];
        }

        return match ($qualifierQ) {
            self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::ACTIVATION,
            self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::RESTORAL,
            self::QUALIFIER_PREVIOUSLY_REPORTED => SecurityEventQualifier::CONDITION_PERSISTS,
            default => SecurityEventQualifier::UNKNOWN_QUALIFIER,
        };
    }

    protected function getEventMapping(string $eventCodeEee): array
    {
        if (isset($this->eventCodeMappings[$eventCodeEee])) {
            return $this->eventCodeMappings[$eventCodeEee];
        }

        Log::warning("Unknown Contact ID event code (EEE): {$eventCodeEee}");

        return [
            'event_type' => SecurityEventType::UNKNOWN_EVENT_TYPE,
            'category' => SecurityEventCategory::UNCLASSIFIED_EVENT,
            'description' => "Unknown Event Code {$eventCodeEee}",
            'priority' => 3, // Medium priority for review
        ];
    }

    protected function isZoneOrUserCode(string $eventCodeEee): string
    {
        $firstDigit = substr($eventCodeEee, 0, 1);

        if (in_array($firstDigit, ['1', '2', '3', '5'])) {
            // Exception: 373/374 are often Tamper/Trouble related to Fire Zones/System
            if (in_array($eventCodeEee, ['373', '374'])) {
                return 'zone';
            } // Or 'none' if system level fire tamper

            return 'zone';
        }
        if ($firstDigit === '4') {
            // Common Arm/Disarm by user, User access codes
            if (
                in_array($eventCodeEee, [
                    '400',
                    '401',
                    '403',
                    '406',
                    '407',
                    '408',
                    '409', // Arm/Disarm, Cancel
                    '411',
                    '412', // Download start/end (often user/technician initiated)
                    '441', // Armed Stay - User
                    '442', // Armed Away - User
                    '450', // Exception (User code related)
                    '451', // Early Open/Close User
                    '452', // Late Open/Close User
                    '455', // Auto-Arm Failed (often system/partition level, but can be tied to last user who armed) - debatable
                    '459', // Recent Closing
                    '461', // Wrong Code Entry
                    // Add other user-centric 4xx codes
                ])
            ) {
                return 'user';
            }

            // Other 4xx might be system-level or partition related
            return 'none';
        }
        if ($firstDigit === '6') {
            // Most 6xx are system level (tests, troubles)
            if (
                in_array($eventCodeEee, [
                    '601',
                    '602',
                    '606',
                    '607',
                    '621',
                    '623',
                    '624',
                    '625',
                    '626', // System Tests, Log %, Time/Date
                    '627',
                    '628', // Program mode
                    '654', // System Inactivity (can be zone/area specific)
                ])
            ) {
                if ($eventCodeEee === '654') {
                    return 'zone';
                } // Often area/zone inactivity

                return 'none'; // System level
            }

            return 'none'; // Default for other 6xx
        }

        return 'none'; // Default if not clearly defined (e.g. 0xx, 7xx, etc.)
    }

    protected function getEventMappingAndQualify(string $qualifierQ, string $eventCodeEee): array
    {
        $baseMapping = $this->getEventMapping($eventCodeEee); // Gets the base definition

        // If the base mapping indicates dynamic interpretation based on qualifier
        if (isset($baseMapping['_dynamic_mapping'])) {
            if (isset($baseMapping['type_map'][$qualifierQ]) && $baseMapping['type_map'][$qualifierQ] instanceof SecurityEventType) {
                $baseMapping['event_type'] = $baseMapping['type_map'][$qualifierQ];
            }
            if (isset($baseMapping['qualifier_map'][$qualifierQ]) && $baseMapping['qualifier_map'][$qualifierQ] instanceof SecurityEventQualifier) {
                $baseMapping['qualifier'] = $baseMapping['qualifier_map'][$qualifierQ];
            }
        }
        // Ensure event_type and qualifier are always set to an enum instance or a default
        $baseMapping['event_type'] = $baseMapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $baseMapping['qualifier'] = $baseMapping['qualifier'] ?? $this->determineEventQualifier($qualifierQ, $baseMapping['event_type'], $baseMapping);

        return $baseMapping;
    }

    protected function initializeEventCodeMappings(): void
    {
        // This mapping needs to be very comprehensive.
        // Format: 'EEE_CODE' => ['event_type' => SecurityEventType, 'category' => SecurityEventCategory, 'description' => string, 'priority' => int, 'qualifier_map' (optional) => [Q => SecurityEventQualifier], 'type_map' (optional) => [Q => SecurityEventType]]
        $this->eventCodeMappings = [
            // --- MEDICAL ALARMS ---
            '100' => ['event_type' => SecurityEventType::MEDICAL_EMERGENCY_ASSISTANCE, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Medical Alarm', 'priority' => 5],
            '101' => ['event_type' => SecurityEventType::MEDICAL_PENDANT_ACTIVATION, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Medical Pendant Alarm', 'priority' => 5],
            '102' => ['event_type' => SecurityEventType::MEDICAL_FALL_DETECTED, 'category' => SecurityEventCategory::ALARM_MEDICAL, 'description' => 'Medical Fall Detector', 'priority' => 5], // Assuming you have this in SecurityEventType

            // --- FIRE ALARMS ---
            '110' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Fire Alarm (Generic Smoke/Heat)', 'priority' => 5], // Often generic fire
            '111' => ['event_type' => SecurityEventType::FIRE_SMOKE_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Smoke Detector Activation', 'priority' => 5],
            '112' => ['event_type' => SecurityEventType::FIRE_MANUAL_PULL_STATION, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Fire Pull Station', 'priority' => 5],
            '113' => ['event_type' => SecurityEventType::FIRE_SPRINKLER_WATERFLOW, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Sprinkler Waterflow', 'priority' => 5],
            '114' => ['event_type' => SecurityEventType::FIRE_HEAT_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Heat Detector Activation', 'priority' => 5],
            // ... other specific fire types like FIRE_DUCT_DETECTOR_ACTIVATION, FIRE_FLAME_DETECTOR if mapped to CID codes
            '117' => ['event_type' => SecurityEventType::FIRE_FLAME_DETECTOR, 'category' => SecurityEventCategory::ALARM_FIRE, 'description' => 'Flame Detected', 'priority' => 5], // Example
            '158' => ['event_type' => SecurityEventType::FIRE_CARBON_MONOXIDE, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Carbon Monoxide Detected', 'priority' => 5], // Or ALARM_FIRE category

            // --- PANIC / DURESS ALARMS ---
            '120' => ['event_type' => SecurityEventType::PANIC_HOLDUP_BUTTON, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Panic Alarm (Fixed/Holdup)', 'priority' => 5],
            '121' => ['event_type' => SecurityEventType::PANIC_DURESS_CODE_USED, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Duress Code Used', 'priority' => 5],
            '122' => ['event_type' => SecurityEventType::PANIC_SILENT_MANUAL, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Silent Panic (Manual)', 'priority' => 5],
            '123' => ['event_type' => SecurityEventType::PANIC_AUDIBLE_MANUAL, 'category' => SecurityEventCategory::ALARM_PANIC_DURESS, 'description' => 'Audible Panic (Manual)', 'priority' => 5],
            // ... other panic types like PANIC_KEYFOB_ACTIVATION if mapped

            // --- BURGLARY ALARMS ---
            '130' => ['event_type' => SecurityEventType::BURGLARY_INTERIOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Burglary (Generic Interior/Motion)', 'priority' => 5],
            '131' => ['event_type' => SecurityEventType::BURGLARY_PERIMETER, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Perimeter Burglary (Door/Window)', 'priority' => 5],
            '132' => ['event_type' => SecurityEventType::BURGLARY_INTERIOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Interior Burglary', 'priority' => 5],
            '133' => ['event_type' => SecurityEventType::BURGLARY_24_HOUR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => '24-Hour Zone Burglary (e.g., Safe)', 'priority' => 5],
            '134' => ['event_type' => SecurityEventType::BURGLARY_DOOR_WINDOW_OPENED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Entry/Exit Zone Burglary', 'priority' => 5],
            '135' => ['event_type' => SecurityEventType::BURGLARY_MOTION_DETECTED, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Interior Follower Burglary (Motion)', 'priority' => 5],
            '136' => ['event_type' => SecurityEventType::BURGLARY_PERIMETER, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Outdoor Burglary', 'priority' => 4], // If you have a more specific outdoor type
            '137' => ['event_type' => SecurityEventType::TAMPER_CONTROL_PANEL, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Tamper (Panel/Device)', 'priority' => 4], // Or TAMPER_DEVICE_SENSOR
            '139' => ['event_type' => SecurityEventType::BURGLARY_ASSET_PROTECTION, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Confirmed/Cross-Zoned Burglary', 'priority' => 5],
            '162' => ['event_type' => SecurityEventType::BURGLARY_GLASS_BREAK_DETECTOR, 'category' => SecurityEventCategory::ALARM_BURGLARY, 'description' => 'Glass Break Detected', 'priority' => 5],

            // --- ENVIRONMENTAL HAZARDS ---
            '150' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => '24-Hr Non-Burglary (Generic Env.)', 'priority' => 4], // Generic start
            '151' => ['event_type' => SecurityEventType::ENV_GAS_LEAK_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Gas Detected', 'priority' => 5],
            '154' => ['event_type' => SecurityEventType::ENV_LOW_TEMPERATURE_LIMIT, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Low Temperature Detected', 'priority' => 4],
            '155' => ['event_type' => SecurityEventType::ENV_HIGH_TEMPERATURE_LIMIT, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'High Temperature Detected', 'priority' => 4],
            '157' => ['event_type' => SecurityEventType::ENV_FLOOD_WATER_DETECTED, 'category' => SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD, 'description' => 'Flood/Water Detected', 'priority' => 4],

            // --- SYSTEM TROUBLES (NON-CRITICAL Category, but some types can be higher priority) ---
            '300' => ['event_type' => SecurityEventType::GENERIC_TROUBLE_UNSPECIFIED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'System Trouble (Generic)', 'priority' => 2],
            '301' => ['event_type' => SecurityEventType::TROUBLE_AC_POWER_LOSS, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'AC Power Loss', 'priority' => 2],
            '302' => ['event_type' => SecurityEventType::TROUBLE_PANEL_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Panel Low Battery', 'priority' => 2],
            '305' => ['event_type' => SecurityEventType::OP_SYSTEM_REBOOT_OR_RESET, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'System Reset/Reboot', 'priority' => 1],
            '309' => ['event_type' => SecurityEventType::TEST_BATTERY_CONDITION, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Battery Test Failure', 'priority' => 2, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_FAIL], // Could also be TROUBLE_PANEL_LOW_BATTERY
            '311' => ['event_type' => SecurityEventType::TROUBLE_PANEL_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Panel Battery Missing/Dead', 'priority' => 3],
            '333' => ['event_type' => SecurityEventType::TROUBLE_EXPANSION_MODULE_OFFLINE, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Expansion Module Trouble', 'priority' => 3],
            '351' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Primary Comm Path Fault (Telco 1)', 'priority' => 3],
            '353' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Primary Comm Path Fault (Radio/Cell)', 'priority' => 3],
            '354' => ['event_type' => SecurityEventType::TROUBLE_COMM_PATH_PRIMARY, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Failure to Communicate Event', 'priority' => 3], // This is supervisory
            '373' => ['event_type' => SecurityEventType::TAMPER_ZONE_WIRING, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Fire Zone Tamper/Trouble', 'priority' => 3], // Can be ALARM_SYSTEM_TAMPER
            '381' => ['event_type' => SecurityEventType::TROUBLE_RF_JAMMING_DETECTED, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'RF Jamming / Wireless Supervision Loss', 'priority' => 3],
            '383' => ['event_type' => SecurityEventType::TAMPER_DEVICE_SENSOR, 'category' => SecurityEventCategory::ALARM_SYSTEM_TAMPER, 'description' => 'Wireless Device Tamper', 'priority' => 3],
            '384' => ['event_type' => SecurityEventType::TROUBLE_DEVICE_LOW_BATTERY, 'category' => SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL, 'description' => 'Wireless Device Low Battery', 'priority' => 2],

            // --- SYSTEM OPERATIONS / ACCESS ---
            '400' => [
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE, // Base, overridden by type_map
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Scheduled Arm/Disarm (Partition)',
                'priority' => 1,
                'qualifier_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED,
                ],
                'type_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_AUTO_SCHEDULE,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE,
                ],
                '_dynamic_mapping' => true,
            ],
            '401' => [
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_DISARM_BY_USER, // Base, overridden by type_map
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Arm/Disarm by User',
                'priority' => 1,
                'qualifier_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED,
                ],
                'type_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_BY_USER,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_BY_USER,
                ],
                '_dynamic_mapping' => true,
            ],
            '403' => [ // Automatic Arm/Disarm - System Wide
                'event_type' => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE, // Base
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Automatic System Arm/Disarm',
                'priority' => 1,
                'qualifier_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::SYSTEM_UNSECURED_DISARMED,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::SYSTEM_SECURED_ARMED,
                ],
                'type_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_SYSTEM_DISARM_AUTO_SCHEDULE,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_SYSTEM_ARM_AUTO_SCHEDULE,
                ],
                '_dynamic_mapping' => true,
            ],
            '406' => ['event_type' => SecurityEventType::OP_SYSTEM_DISARM_BY_USER, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Cancel by User (after alarm)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::USER_INITIATED_ACTION], // Qualifier implies disarm
            '408' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_AWAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Quick Arm - Away Mode', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '409' => ['event_type' => SecurityEventType::OP_SYSTEM_ARM_STAY, 'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS, 'description' => 'Quick Arm - Stay Mode', 'priority' => 1, 'qualifier' => SecurityEventQualifier::SYSTEM_SECURED_ARMED],
            '411' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_START, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Remote Session Started (Download/Upload)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '412' => ['event_type' => SecurityEventType::OP_REMOTE_SESSION_END, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Remote Session Ended (Download/Upload Complete)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '455' => ['event_type' => SecurityEventType::SUPERVISORY_FAILURE_TO_ARM_SCHEDULE, 'category' => SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM, 'description' => 'Auto-Arm Failed', 'priority' => 3, 'qualifier' => SecurityEventQualifier::SCHEDULE_VIOLATION],
            '461' => ['event_type' => SecurityEventType::OP_DOOR_ACCESS_DENIED, 'category' => SecurityEventCategory::ACCESS_CONTROL_EVENT, 'description' => 'Wrong Code Entry (Access)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::ACCESS_DENIED_INVALID],

            // --- BYPASSES ---
            '570' => [
                'event_type' => SecurityEventType::OP_ZONE_BYPASSED, // Base, overridden by type_map
                'category' => SecurityEventCategory::SYSTEM_OPERATION_ACCESS,
                'description' => 'Zone Bypass/Unbypass',
                'priority' => 1,
                'qualifier_map' => [
                    self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::BYPASS_ACTIVATED,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::BYPASS_DEACTIVATED,
                ],
                'type_map' => [ // EventType changes to reflect unbypass clearly
                    self::QUALIFIER_NEW_EVENT => SecurityEventType::OP_ZONE_BYPASSED,
                    self::QUALIFIER_RESTORAL_SECURE => SecurityEventType::OP_ZONE_UNBYPASSED,
                ],
                '_dynamic_mapping' => true,
            ],

            // --- TESTS ---
            '601' => ['event_type' => SecurityEventType::TEST_MANUAL_BY_USER_INSTALLER, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Manual Test Initiated', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_INITIATED],
            '602' => ['event_type' => SecurityEventType::TEST_PERIODIC_SYSTEM_AUTO, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Periodic Test Report', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_COMPLETED_PASS],
            '606' => ['event_type' => SecurityEventType::TEST_COMMUNICATION, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Listen-in to Follow (Audio Verification Test)', 'priority' => 1, 'qualifier' => SecurityEventQualifier::INFORMATION_REPORT],
            '607' => ['event_type' => SecurityEventType::TEST_WALK_MODE_ACTIVE, 'category' => SecurityEventCategory::SYSTEM_TEST_SIGNAL, 'description' => 'Walk Test Mode', 'priority' => 1, 'qualifier' => SecurityEventQualifier::TEST_IN_PROGRESS],
            '627' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_ENTERED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Program Mode Entered', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_ENTERED],
            '628' => ['event_type' => SecurityEventType::OP_SYSTEM_PROGRAMMING_EXITED, 'category' => SecurityEventCategory::INFORMATIONAL_LOG, 'description' => 'Program Mode Exited', 'priority' => 1, 'qualifier' => SecurityEventQualifier::PROGRAMMING_MODE_EXITED],

            // ... Add many more specific mappings ...
        ];

        // This loop to mark dynamic mappings is now part of initializeEventCodeMappings
        // foreach ($this->eventCodeMappings as $code => $mapping) {
        //     if (isset($mapping['qualifier_map']) || isset($mapping['type_map'])) {
        //         $this->eventCodeMappings[$code]['_dynamic_mapping'] = true;
        //     }
        // }
    }
}
