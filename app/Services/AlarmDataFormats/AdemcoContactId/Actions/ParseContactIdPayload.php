<?php

namespace App\Services\AlarmDataFormats\AdemcoContactId\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class ParseContactIdPayload
{
    use AsAction;

    /**
     * Parses the core Contact ID payload string (QEEEGGZZZ).
     *
     * @param  string  $contactIdPayload  The "QEEEGGZZZ" part, already trimmed and spaces removed.
     * @return array|null Parsed data as [qualifier_q, event_code_eee, partition_gg, zone_user_zzz] or null.
     */
    public function handle(string $contactIdPayload): ?array
    {
        $cleanedPayload = preg_replace('/[^A-Z0-9]/', '', strtoupper($contactIdPayload));

        // Q EEE GG ZZZ (1, 3, 2, 3 = 9 chars)
        // Q EEE GG    (1, 3, 2   = 6 chars)
        // Q EEE       (1, 3     = 4 chars)
        if (preg_match('/^([136])([0-9A-F]{3})(?:([0-9A-F]{2})(?:([0-9A-F]{3}))?)?$/', $cleanedPayload, $matches)) {
            return [
                'qualifier_q' => $matches[1],
                'event_code_eee' => $matches[2],
                'partition_gg' => $matches[3] ?? '00',
                'zone_user_zzz' => $matches[4] ?? '000',
            ];
        }

        Log::debug('AdemcoContactId: Contact ID payload regex did not match.', ['payload' => $cleanedPayload]);

        return null;
    }
}
