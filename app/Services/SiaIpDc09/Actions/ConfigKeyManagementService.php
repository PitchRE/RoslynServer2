<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigKeyManagementService implements KeyManagementService
{
    /**
     * Retrieves the decryption key from the application's configuration.
     * For SIA DC-09, this typically means a pre-shared key.
     * This implementation uses a default key from config.
     *
     * @param  string  $panelAccountNumber  (Not used in this basic implementation but part of contract for future extension)
     * @param  string|null  $receiverNumber  (Not used in this basic implementation)
     * @param  string|null  $linePrefix  (Not used in this basic implementation)
     * @param  string  $cipher  The cipher being used (e.g., 'aes-128-cbc'), to potentially validate key length.
     * @return string|null The raw binary decryption key, or null if not found or invalid.
     */
    public function getKey(
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix,
        string $cipher
    ): ?string {
        $keyHex = Config::get('SiaIpDc09.encryption.default_key_hex');

        if (empty($keyHex)) {
            Log::error('SIA Decryption: Default encryption key (SIA_DEFAULT_KEY_HEX) is not configured.');

            return null;
        }

        // Convert hex key to binary
        $binaryKey = @hex2bin($keyHex);

        if ($binaryKey === false) {
            Log::error('SIA Decryption: Configured default key is not valid hexadecimal.', ['key_hex_preview' => substr($keyHex, 0, 10).'...']);

            return null;
        }

        // Optional: Validate key length against cipher requirements
        $expectedKeyLengthBytes = match (strtolower($cipher)) {
            'aes-128-cbc' => 16,
            'aes-192-cbc' => 24,
            'aes-256-cbc' => 32,
            default => null, // Unknown cipher, cannot validate length
        };

        if ($expectedKeyLengthBytes !== null && strlen($binaryKey) !== $expectedKeyLengthBytes) {
            Log::error('SIA Decryption: Configured key length does not match cipher requirements.', [
                'cipher' => $cipher,
                'expected_length_bytes' => $expectedKeyLengthBytes,
                'actual_length_bytes' => strlen($binaryKey),
                'account' => $panelAccountNumber,
            ]);

            return null;
        }

        return $binaryKey;
    }
}
