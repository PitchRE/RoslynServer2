<?php

namespace App\Services\AlarmDataFormats\AdemcoContactId\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsJob;

class InterpretAdemcoContactIdData
{
    use AsAction;
    use AsJob;

    // Constants for qualifiers can be useful if this class needs to reference them directly,
    // though the primary definitions are now in ContactIdEventCodeMaps
    protected const QUALIFIER_NEW_EVENT = '1';

    protected const QUALIFIER_RESTORAL_SECURE = '3';

    protected const QUALIFIER_PREVIOUSLY_REPORTED = '6';

    /**
     * Handle the interpretation of Ademco Contact ID data.
     *
     * @param  string  $rawMessageData  Example: "#ACCOUNT|QEEE GG ZZZ" or "QEEE GG ZZZ"
     * @param  string|null  $panelAccountNumberFromIdentifier  Account number from a broader identifier.
     * @param  CarbonImmutable  $occurredAt  Time of event occurrence or initial reception.
     * @param  int|null  $deviceId  Optional: Known ID of the device.
     */
    public function handle(
        string $rawMessageData,
        ?string $panelAccountNumberFromIdentifier,
        CarbonImmutable $occurredAt,
        ?int $deviceId = null
    ) {
        $preParsed = $this->preParseMessageData($rawMessageData, $panelAccountNumberFromIdentifier);
        if (!$preParsed) {
            // Log is handled in preParseMessageData
            return null;
        }

        $accountNumber = $preParsed['account_number'];
        $contactIdPayload = $preParsed['contact_id_payload'];

        // Assuming ParseContactIdPayload action exists and is callable via ::run()
        $parsedPayload = ParseContactIdPayload::run($contactIdPayload);
        if (!$parsedPayload) {
            Log::warning('AdemcoContactId: Failed to parse Contact ID payload.', [
                'payload' => $contactIdPayload,
                'account' => $accountNumber,
                'device_id' => $deviceId,
            ]);

            return null;
        }

        $mapping = $this->getEventMappingAndQualify($parsedPayload['qualifier_q'], $parsedPayload['event_code_eee']);

        $eventType = $mapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $eventCategory = $mapping['category'] ?? SecurityEventCategory::UNCLASSIFIED_EVENT;
        $eventQualifier = $mapping['qualifier'] ?? $this->determineEventQualifier($parsedPayload['qualifier_q'], $eventType, $mapping);
        $priority = $mapping['priority'] ?? 3; // Default priority
        $rawDescription = $mapping['description'] ?? "Contact ID Event: {$parsedPayload['event_code_eee']}";

        $rawZoneIdentifier = null;
        $rawUserIdentifier = null;
        $identifierType = $this->isZoneOrUserCode($parsedPayload['event_code_eee']);

        if ($identifierType === 'zone' && $parsedPayload['zone_user_zzz'] !== '000') {
            $rawZoneIdentifier = $parsedPayload['zone_user_zzz'];
        } elseif ($identifierType === 'user' && $parsedPayload['zone_user_zzz'] !== '000') {
            $rawUserIdentifier = $parsedPayload['zone_user_zzz'];
        }

        $securityEvent = new SecurityEvent; // Instantiate empty

        $securityEvent->occurred_at = $occurredAt;
        $securityEvent->source_protocol = 'ADM-CID';
        $securityEvent->raw_event_code = $parsedPayload['qualifier_q'] . $parsedPayload['event_code_eee'];
        $securityEvent->raw_event_description = $rawDescription;
        $securityEvent->raw_device_identifier = $accountNumber;
        $securityEvent->device_id = $deviceId;
        $securityEvent->raw_device_identifier = $deviceId ? (string) $deviceId : null;

        // Crucially, assign the property before you try to read it
        $securityEvent->raw_partition_identifier = $parsedPayload['partition_gg'] === '00' ? null : $parsedPayload['partition_gg'];

        $securityEvent->raw_zone_identifier = $rawZoneIdentifier;
        //  $securityEvent->raw_user_identifier = $rawUserIdentifier;
        $securityEvent->event_category = $eventCategory;
        $securityEvent->event_type = $eventType;
        $securityEvent->event_qualifier = $eventQualifier;
        $securityEvent->priority = $priority;
        $securityEvent->message_details = json_encode($parsedPayload);
        $securityEvent->status = SecurityEventStatus::NEW; // This will use the default from $attributes if not set here

        // A basic normalized description; the enricher can improve this.
        $descParts = ["Contact ID: {$rawDescription}", "Acct: {$accountNumber}"];
        if ($securityEvent->raw_partition_identifier) {
            $descParts[] = "Part: {$securityEvent->raw_partition_identifier}";
        }
        if ($rawZoneIdentifier) {
            $descParts[] = "Zone: {$rawZoneIdentifier}";
        }
        if ($rawUserIdentifier) {
            $descParts[] = "User: {$rawUserIdentifier}";
        }
        $securityEvent->normalized_description = implode(' - ', $descParts);

        $securityEvent->save();


    }

    /**
     * Pre-parses raw message data to separate account number and Contact ID payload.
     * Handles formats like "#ACCOUNT|PAYLOAD" or just "PAYLOAD".
     */
    protected function preParseMessageData(string $rawMessageData, ?string $panelAccountNumberFromIdentifier): ?array
    {
        $accountNumber = $panelAccountNumberFromIdentifier;
        $contactIdPayload = $rawMessageData;

        if (str_contains($rawMessageData, '|')) {
            $parts = explode('|', $rawMessageData, 2);
            $potentialAccount = ltrim($parts[0], '#');
            if (!empty($potentialAccount)) {
                if ($accountNumber && $accountNumber !== $potentialAccount && !empty($accountNumber)) { // Only log if identifier acc was also present
                    Log::info("AdemcoContactId: Account number mismatch between identifier ('{$accountNumber}') and message_data ('{$potentialAccount}'). Using account from message_data.", ['raw_message_data' => $rawMessageData]);
                }
                $accountNumber = $potentialAccount;
            }
            $contactIdPayload = $parts[1] ?? '';
        }

        if (empty($accountNumber)) {
            Log::warning('AdemcoContactId: Account number could not be determined.', ['raw_message_data' => $rawMessageData, 'identifier_account' => $panelAccountNumberFromIdentifier]);

            return null;
        }

        $contactIdPayload = trim(str_replace(' ', '', $contactIdPayload));
        if (empty($contactIdPayload)) {
            Log::warning('AdemcoContactId: Contact ID payload is empty after pre-parsing.', ['raw_message_data' => $rawMessageData, 'account' => $accountNumber]);

            return null;
        }

        return ['account_number' => $accountNumber, 'contact_id_payload' => $contactIdPayload];
    }

    /**
     * Determines the normalized EventQualifier based on Contact ID qualifier and event type context.
     * This acts as a fallback if the dynamic mapping from ContactIdEventCodeMaps doesn't specify one.
     */
    protected function determineEventQualifier(string $qualifierQ, SecurityEventType $eventType, array $mapping): SecurityEventQualifier
    {
        // If $mapping['qualifier'] was set by getEventMappingAndQualify, it would be used.
        // This is the generic fallback based only on Q.
        return match ($qualifierQ) {
            self::QUALIFIER_NEW_EVENT => SecurityEventQualifier::ACTIVATION,
            self::QUALIFIER_RESTORAL_SECURE => SecurityEventQualifier::RESTORAL,
            self::QUALIFIER_PREVIOUSLY_REPORTED => SecurityEventQualifier::CONDITION_PERSISTS,
            default => SecurityEventQualifier::UNKNOWN_QUALIFIER,
        };
    }

    /**
     * Retrieves the base mapping for an EEE code and then applies dynamic qualifier-based overrides.
     */
    protected function getEventMappingAndQualify(string $qualifierQ, string $eventCodeEee): array
    {
        $baseMapping = ContactIdEventCodeMaps::getMapping($eventCodeEee);

        if (!$baseMapping) {
            Log::warning('AdemcoContactId: Unknown EEE code, using default unknown mapping.', ['eee_code' => $eventCodeEee]);
            // Use the _UNKNOWN_ mapping from ContactIdEventCodeMaps if defined, or a hardcoded default
            $baseMapping = ContactIdEventCodeMaps::getMapping('_UNKNOWN_') ?? [
                'event_type' => SecurityEventType::UNKNOWN_EVENT_TYPE,
                'category' => SecurityEventCategory::UNCLASSIFIED_EVENT,
                'description' => "Unknown EEE Code {$eventCodeEee}",
                'priority' => 3, // Medium priority for review
            ];
        }

        $finalMapping = $baseMapping; // Start with the base

        // Apply dynamic mapping if present in the retrieved base mapping
        if (isset($baseMapping['_dynamic_mapping'])) {
            if (isset($baseMapping['type_map'][$qualifierQ]) && $baseMapping['type_map'][$qualifierQ] instanceof SecurityEventType) {
                $finalMapping['event_type'] = $baseMapping['type_map'][$qualifierQ];
            }
            if (isset($baseMapping['qualifier_map'][$qualifierQ]) && $baseMapping['qualifier_map'][$qualifierQ] instanceof SecurityEventQualifier) {
                $finalMapping['qualifier'] = $baseMapping['qualifier_map'][$qualifierQ]; // This will be the primary qualifier
            }
        }

        // Ensure event_type and qualifier are always set to an enum instance or a default
        // If 'qualifier' was not set by qualifier_map, then determineEventQualifier will provide a generic one.
        $finalMapping['event_type'] = $finalMapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $finalMapping['qualifier'] = $finalMapping['qualifier'] ?? $this->determineEventQualifier($qualifierQ, $finalMapping['event_type'], $finalMapping); // Pass $finalMapping for context

        return $finalMapping;
    }

    /**
     * Determines if the ZZZ part of Contact ID refers to a Zone or a User
     * based on the EEE event code.
     */
    protected function isZoneOrUserCode(string $eventCodeEee): string
    {
        $firstDigit = substr($eventCodeEee, 0, 1);

        // General Contact ID guidelines (can be panel specific)
        if (in_array($firstDigit, ['1', '2', '3', '5'])) {
            // Exceptions within these ranges might exist, e.g., specific 3xx system troubles
            if (in_array($eventCodeEee, ['373', '374'])) {
                return 'zone';
            } // Fire zone trouble/tamper

            return 'zone';
        }
        if ($firstDigit === '4') {
            // User-related events
            if (
                in_array($eventCodeEee, [
                    '400',
                    '401',
                    '403',
                    '404',
                    '405',
                    '406',
                    '407',
                    '408',
                    '409', // Arm/Disarm, Cancel by user types
                    '411',
                    '412',
                    '413',
                    '414',
                    '415',
                    '416', // Remote access/programming
                    '441',
                    '442', // Armed Stay/Away by user
                    '450',
                    '451',
                    '452', // User code related, Early/Late Open/Close by user
                    '457', // Exit Error by user
                    '459', // Recent Closing (by user)
                    '461', // Wrong Code Entry
                    // Add more user-centric 4xx if known
                ])
            ) {
                return 'user';
            }

            // Other 4xx (e.g., 402 - Armed by Non-User, 455 - Auto-Arm Fail) are often system/partition
            return 'none';
        }
        if ($firstDigit === '6') {
            // System tests, troubles, operations
            if (
                in_array($eventCodeEee, [
                    '601',
                    '602',
                    '606',
                    '607',
                    '608', // Tests
                    '621',
                    '622',
                    '623',
                    '624',
                    '625',
                    '626', // System status, Log %
                    '627',
                    '628', // Program mode
                ])
            ) {
                return 'none'; // System or partition level
            }
            if ($eventCodeEee === '654') {
                return 'zone';
            } // System Inactivity (often by area/zone)

            return 'none'; // Default for other 6xx
        }

        // Other ranges (0xx, 7xx, 8xx, 9xx) are less common or vendor-specific
        return 'none';
    }
}
