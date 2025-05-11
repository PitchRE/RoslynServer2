<?php

namespace App\Services\AlarmDataFormats\Actions;

use App\Enums\SecurityEventStatus;
use App\Models\Device;
use App\Models\PanelUser;
use App\Models\Partition;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\Zone;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class EnrichSecurityEventWithRelations
{
    use AsAction;

    public function handle(SecurityEvent $securityEvent): SecurityEvent
    {
        $device = null;
        $site = null;
        $partition = null;
        $zone = null;
        $panelUser = null;

        // --- 1. Attempt to identify Device ---
        if ($securityEvent->device_id) {
            $device = Device::with('site')->find($securityEvent->device_id);
            if (! $device) {
                Log::warning('Enrichment: Pre-set device_id not found in database. Clearing device_id.', ['device_id_from_event' => $securityEvent->device_id, 'cid_account' => $securityEvent->raw_account_identifier]);
                $securityEvent->device_id = null;
            }
        }

        if (! $device && (! empty($securityEvent->raw_device_identifier) || ! empty($securityEvent->raw_account_identifier))) {
            $deviceQuery = Device::query()->with('site');
            if (! empty($securityEvent->raw_device_identifier)) {
                $deviceQuery->where('identifier', $securityEvent->raw_device_identifier);
            }
            if (! empty($securityEvent->raw_account_identifier)) {
                if (! empty($securityEvent->raw_device_identifier)) {
                    $deviceQuery->orWhere('identifier', $securityEvent->raw_account_identifier);
                } else {
                    $deviceQuery->where('identifier', $securityEvent->raw_account_identifier);
                }
            }
            $device = $deviceQuery->first();
        }

        // --- Set Status and Foreign Keys based on Device lookup ---
        if ($device) {
            $securityEvent->device_id = $device->id; // Ensure device_id on event is updated if found this way

            $site = $device->site;

        } else { // Device was not found by any means
            Log::info('Enrichment: Device could not be identified for event. Site association will also fail.', [
                'cid_account' => $securityEvent->raw_account_identifier,
                'csr_device_identifier' => $securityEvent->raw_device_identifier,
                'event_raw_code' => $securityEvent->raw_event_code,
            ]);
            $securityEvent->status = SecurityEventStatus::PENDING_DEVICE_IDENTIFICATION;
            $this->enhanceNormalizedDescription($securityEvent, null, null, null, null, null);

            return $securityEvent;
        }
        // If we reach here, $device is a valid Device model instance and $site is its valid Site model instance.

        // --- Find Partition (device is guaranteed to be non-null here) ---
        if ($securityEvent->raw_partition_identifier) { // Corrected: Removed redundant '$device &&'
            $partition = Partition::where('device_id', $device->id) // Use $device->id which is now confirmed
                ->where('partition_number', $securityEvent->raw_partition_identifier)
                ->first();
            if ($partition) {
                $securityEvent->partition_id = $partition->id;
            } else {
                Log::info('Enrichment: Partition not found.', ['device_id' => $device->id, 'raw_partition_id' => $securityEvent->raw_partition_identifier]);
            }
        }

        // --- Find Zone (device is guaranteed to be non-null here) ---
        if ($securityEvent->raw_zone_identifier) { // Corrected: Removed redundant '$device &&'
            $zoneQuery = Zone::where('device_id', $device->id) // Use $device->id
                ->where('zone_number', $securityEvent->raw_zone_identifier);
            if ($securityEvent->partition_id) {
                $zoneQuery->where('partition_id', $securityEvent->partition_id);
            }
            $zone = $zoneQuery->first();
            if ($zone) {
                $securityEvent->zone_id = $zone->id;
            } else {
                Log::info('Enrichment: Zone not found.', ['device_id' => $device->id, 'partition_id' => $securityEvent->partition_id, 'raw_zone_id' => $securityEvent->raw_zone_identifier]);
            }
        }

        // --- Find Panel User (site is guaranteed to be non-null here) ---
        if ($securityEvent->raw_panel_user_identifier) { // Corrected: Removed redundant '$site &&'
            $panelUser = PanelUser::where('site_id', $site->id) // Use $site->id
                ->where('panel_user_code', $securityEvent->raw_panel_user_identifier)
                ->first();
            if ($panelUser) {
                $securityEvent->panel_user_id = $panelUser->id;
            } else {
                Log::info('Enrichment: Panel user not found.', ['site_id' => $site->id, 'raw_user_id' => $securityEvent->raw_panel_user_identifier]);
            }
        }

        // --- Enhance Normalized Description ---
        $this->enhanceNormalizedDescription($securityEvent, $site, $device, $partition, $zone, $panelUser);

        return $securityEvent;
    }

    // enhanceNormalizedDescription method remains the same
    protected function enhanceNormalizedDescription(
        SecurityEvent $securityEvent,
        ?Site $site,
        ?Device $device,
        ?Partition $partition,
        ?Zone $zone,
        ?PanelUser $panelUser
    ): void {
        $descParts = [];
        $descParts[] = $securityEvent->raw_event_description ?: "Event Code: {$securityEvent->raw_event_code}";

        if ($site) {
            $descParts[] = "Site: {$site->name} ({$securityEvent->raw_account_identifier})";
        } elseif ($securityEvent->raw_account_identifier) {
            $descParts[] = "CID Acct: {$securityEvent->raw_account_identifier}";
        }

        if ($device) {
            $deviceName = $device->name ?? $device->identifier;
            if ($deviceName && $deviceName !== $securityEvent->raw_device_identifier && $deviceName !== $securityEvent->raw_account_identifier) {
                $descParts[] = "Panel: {$deviceName}";
            } elseif ($securityEvent->raw_device_identifier && $securityEvent->raw_device_identifier !== $securityEvent->raw_account_identifier) {
                $descParts[] = "Panel CSR-ID: {$securityEvent->raw_device_identifier}";
            }
        } elseif ($securityEvent->raw_device_identifier && $securityEvent->raw_device_identifier !== $securityEvent->raw_account_identifier) {
            $descParts[] = "Panel CSR-ID: {$securityEvent->raw_device_identifier}";
        }

        if ($partition) {
            $partitionName = $partition->name ?? $securityEvent->raw_partition_identifier;
            $descParts[] = "Part: {$partitionName}";
        } elseif ($securityEvent->raw_partition_identifier) {
            $descParts[] = "Part: {$securityEvent->raw_partition_identifier}";
        }

        if ($zone) {
            $zoneName = $zone->name ?? $securityEvent->raw_zone_identifier;
            $descParts[] = "Zone: {$zoneName}";
        } elseif ($securityEvent->raw_zone_identifier) {
            $descParts[] = "Zone: {$securityEvent->raw_zone_identifier}";
        }

        if ($panelUser) {
            $userName = $panelUser->name ?? $securityEvent->raw_panel_user_identifier;
            $descParts[] = "User: {$userName}";
        } elseif ($securityEvent->raw_panel_user_identifier) {
            $descParts[] = "User: {$securityEvent->raw_panel_user_identifier}";
        }

        $securityEvent->normalized_description = implode(' - ', $descParts);
    }
}
