<?php

namespace App\Services\AlarmDataFormats\AdemcoContactId\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsJob;

class InterpretAdemcoContactIdData
{
    use AsAction;
    use AsJob;

    protected const QUALIFIER_NEW_EVENT = '1';

    protected const QUALIFIER_RESTORAL_SECURE = '3';

    protected const QUALIFIER_PREVIOUSLY_REPORTED = '6';

    /**
     * Handle the interpretation of Ademco Contact ID data.
     *
     * @param  string  $rawMessageData  The Contact ID string, which typically includes an embedded account number
     *                                  (e.g., "#ACCOUNT|QEEE GG ZZZ" or "ACCOUNTQEEE GG ZZZ").
     * @param  CarbonImmutable  $occurredAt  The time the event occurred or was received.
     * @param  string|null  $csrDeviceIdentifier  Optional: The primary identifier used by the CSR to identify the device
     *                                            (e.g., receiver line + panel account, unique panel serial if centrally managed).
     */
    public function handle(
        string $rawMessageData,
        CarbonImmutable $occurredAt,
        ?string $csrDeviceIdentifier, // This is your main identifier arriving at CSR
        Model $sourceMessage
    ): ?SecurityEvent {
        $preParsed = $this->preParseMessageData($rawMessageData);
        if (! $preParsed) {
            return null;
        }

        $contactIdAccountNumber = $preParsed['account_number']; // Account from Contact ID string itself
        $contactIdPayload = $preParsed['contact_id_payload'];     // "QEEEGGZZZ"

        if (empty($contactIdAccountNumber)) {
            Log::warning('AdemcoContactId: Contact ID Account number is missing from raw message data. Cannot proceed with interpretation based on CID account.', [
                'raw_message_data' => $rawMessageData,
                'csr_device_identifier' => $csrDeviceIdentifier,
            ]);
            // If $csrDeviceIdentifier is present, the Enricher might still find the device/site.
            // However, core CID interpretation usually relies on its own account.
            // For now, we proceed but this event might be hard to link without a CID account.
            // Consider if this should return null if contactIdAccountNumber is vital.
            // If $csrDeviceIdentifier is your *only* reliable identifier, then this interpreter's role changes.
            // Let's assume for now that contactIdAccountNumber is important for interpretation context.
            // If it *must* be present, then return null here.
        }

        $parsedPayload = ParseContactIdPayload::run($contactIdPayload);
        if (! $parsedPayload) {
            Log::warning('AdemcoContactId: Failed to parse Contact ID payload.', [
                'payload' => $contactIdPayload,
                'contact_id_account' => $contactIdAccountNumber,
                'csr_device_identifier' => $csrDeviceIdentifier,
            ]);

            return null;
        }

        $mapping = $this->getEventMappingAndQualify($parsedPayload['qualifier_q'], $parsedPayload['event_code_eee']);

        $eventType = $mapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $eventCategory = $mapping['category'] ?? SecurityEventCategory::UNCLASSIFIED_EVENT;
        $eventQualifier = $mapping['qualifier'] ?? $this->determineEventQualifier($parsedPayload['qualifier_q'], $eventType, $mapping);
        $priority = $mapping['priority'] ?? 3;
        $rawDescription = $mapping['description'] ?? "Contact ID Event: {$parsedPayload['event_code_eee']}";

        $localRawZoneIdentifier = null;
        $localRawUserIdentifier = null;
        $identifierType = $this->isZoneOrUserCode($parsedPayload['event_code_eee']);

        if ($identifierType === 'zone' && $parsedPayload['zone_user_zzz'] !== '000') {
            $localRawZoneIdentifier = $parsedPayload['zone_user_zzz'];
        } elseif ($identifierType === 'user' && $parsedPayload['zone_user_zzz'] !== '000') {
            $localRawUserIdentifier = $parsedPayload['zone_user_zzz'];
        }
        $localRawPartitionIdentifier = $parsedPayload['partition_gg'] === '00' ? null : $parsedPayload['partition_gg'];

        $securityEvent = new SecurityEvent([
            'occurred_at' => $occurredAt,
            'source_protocol' => 'ADM-CID',
            'raw_event_code' => $parsedPayload['qualifier_q'].$parsedPayload['event_code_eee'],
            'raw_event_description' => $rawDescription,
            'raw_account_identifier' => $contactIdAccountNumber, // Account from CID string
            'raw_device_identifier' => $csrDeviceIdentifier,    // Main identifier from CSR
            'raw_partition_identifier' => $localRawPartitionIdentifier,
            'raw_zone_identifier' => $localRawZoneIdentifier,
            'raw_panel_user_identifier' => $localRawUserIdentifier,
            'event_category' => $eventCategory,
            'event_type' => $eventType,
            'event_qualifier' => $eventQualifier,
            'priority' => $priority,
            'message_details' => json_encode($parsedPayload + ['cid_account' => $contactIdAccountNumber]), // Add CID account to details
            'status' => SecurityEventStatus::NEW,
        ]);

        $securityEvent->sourceMessage()->associate($sourceMessage);

        $descParts = ["Contact ID: {$rawDescription}"];
        if ($contactIdAccountNumber) {
            $descParts[] = "CID Acct: {$contactIdAccountNumber}";
        }
        if ($csrDeviceIdentifier) {
            $descParts[] = "Dev CSR-ID: {$csrDeviceIdentifier}";
        }
        if ($localRawPartitionIdentifier) {
            $descParts[] = "Part: {$localRawPartitionIdentifier}";
        }
        if ($localRawZoneIdentifier) {
            $descParts[] = "Zone: {$localRawZoneIdentifier}";
        }
        if ($localRawUserIdentifier) {
            $descParts[] = "User: {$localRawUserIdentifier}";
        }
        $securityEvent->normalized_description = implode(' - ', $descParts);

        return $securityEvent;

    }

    /**
     * Pre-parses the raw Contact ID message data to extract the account number and the core payload.
     *
     * @param  string  $rawMessageData  e.g., "#ACCOUNT|QEEE GG ZZZ" or "ACCOUNTQEEE GG ZZZ"
     * @return array|null ['account_number' => string|null, 'contact_id_payload' => string]
     */
    protected function preParseMessageData(string $rawMessageData): ?array
    {
        $accountNumber = null;
        $contactIdPayload = trim($rawMessageData);

        if (str_contains($contactIdPayload, '|')) {
            $parts = explode('|', $contactIdPayload, 2);
            $accountPart = ltrim($parts[0], '#');
            if (! empty($accountPart)) {
                $accountNumber = $accountPart;
            }
            $contactIdPayload = $parts[1] ?? '';
        } else {
            // Attempt to extract if account is prefixed, e.g., "1234113001005"
            // This regex assumes account is 3-6 hex chars, followed by Q (1,3,6), then EEE.
            if (preg_match('/^([0-9A-F]{3,6})([136][0-9A-F]{3}.*)$/', $contactIdPayload, $matches)) {
                $accountNumber = $matches[1];
                $contactIdPayload = $matches[2];
            } else {
                // If no separator and no clear prefix, assume $rawMessageData is payload only, account might be missing from this string
                Log::debug('AdemcoContactId: No clear account prefix or separator in rawMessageData. Assuming payload only or account is missing from this string.', ['raw_message_data' => $rawMessageData]);
                // $accountNumber will remain null
            }
        }

        $contactIdPayload = trim(str_replace(' ', '', $contactIdPayload));
        if (empty($contactIdPayload) && ! $accountNumber) { // If payload became empty AND we didn't find an account, it's a bad message
            Log::warning('AdemcoContactId: Contact ID payload is empty and no account found after pre-parsing.', [
                'raw_message_data' => $rawMessageData,
            ]);

            return null;
        }
        // It's possible to have an account number with an empty payload for some very malformed messages.
        // Or a payload with no account if the account is determined by other means (e.g. dedicated line)
        // The caller will need to decide how to handle a null $accountNumber from here if it's critical.

        return [
            'account_number' => $accountNumber, // Can be null
            'contact_id_payload' => $contactIdPayload,
        ];
    }

    // --- (determineEventQualifier, getEventMappingAndQualify, isZoneOrUserCode methods remain the same) ---
    // These helper methods are defined as in the previous "full code" response which used ContactIdEventCodeMaps
    protected function determineEventQualifier(string $qualifierQ, SecurityEventType $eventType, array $mapping): SecurityEventQualifier
    {
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

    protected function getEventMappingAndQualify(string $qualifierQ, string $eventCodeEee): array
    {
        $baseMapping = ContactIdEventCodeMaps::getMapping($eventCodeEee);

        if (! $baseMapping) {
            Log::warning("AdemcoContactId: Unknown EEE code '{$eventCodeEee}', using default unknown mapping.");
            $baseMapping = ContactIdEventCodeMaps::getMapping('_UNKNOWN_') ?? [ // Fallback to _UNKNOWN_ if defined
                'event_type' => SecurityEventType::UNKNOWN_EVENT_TYPE,
                'category' => SecurityEventCategory::UNCLASSIFIED_EVENT,
                'description' => "Unknown EEE Code {$eventCodeEee}",
                'priority' => 3,
            ];
        }

        $finalMapping = $baseMapping;

        if (isset($baseMapping['_dynamic_mapping'])) {
            if (isset($baseMapping['type_map'][$qualifierQ]) && $baseMapping['type_map'][$qualifierQ] instanceof SecurityEventType) {
                $finalMapping['event_type'] = $baseMapping['type_map'][$qualifierQ];
            }
            if (isset($baseMapping['qualifier_map'][$qualifierQ]) && $baseMapping['qualifier_map'][$qualifierQ] instanceof SecurityEventQualifier) {
                $finalMapping['qualifier'] = $baseMapping['qualifier_map'][$qualifierQ];
            }
        }
        $finalMapping['event_type'] = $finalMapping['event_type'] ?? SecurityEventType::UNKNOWN_EVENT_TYPE;
        $finalMapping['qualifier'] = $finalMapping['qualifier'] ?? $this->determineEventQualifier($qualifierQ, $finalMapping['event_type'], $finalMapping);

        return $finalMapping;
    }

    protected function isZoneOrUserCode(string $eventCodeEee): string
    {
        $firstDigit = substr($eventCodeEee, 0, 1);
        if (in_array($firstDigit, ['1', '2', '3', '5'])) {
            if (in_array($eventCodeEee, ['373', '374'])) {
                return 'zone';
            }

            return 'zone';
        }
        if ($firstDigit === '4') {
            if (in_array($eventCodeEee, ['400', '401', '403', '406', '407', '408', '409', '411', '412', '441', '442', '450', '451', '452', '455', '457', '459', '461'])) {
                return 'user';
            }

            return 'none';
        }
        if ($firstDigit === '6') {
            if (in_array($eventCodeEee, ['601', '602', '606', '607', '608', '621', '622', '623', '624', '625', '626', '627', '628', '654'])) {
                if ($eventCodeEee === '654') {
                    return 'zone';
                }

                return 'none';
            }

            return 'none';
        }

        return 'none';
    }
}
