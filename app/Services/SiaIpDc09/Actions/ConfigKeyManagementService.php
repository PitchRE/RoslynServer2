<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigKeyManagementService implements KeyManagementService
{
    /**
     * Retrieves the default key (binary) and default cipher from config.
     * Ignores panel/receiver/prefix identifiers in this basic implementation.
     *
     * @param  string  $panelAccountNumber  (Not used)
     * @param  string|null  $receiverNumber  (Not used)
     * @param  string|null  $linePrefix  (Not used)
     * @return array{?string, ?string}|null Array [binary key, cipher name] or null on failure.
     */
    public function getKeyAndCipher(
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?array {
        $keyHex = Config::get('SiaIpDc09.encryption.default_key_hex');
        $cipher = strtolower(Config::get('SiaIpDc09.encryption.default_cipher', 'aes-128-cbc')); // Get default cipher

        if (empty($keyHex)) {
            Log::error('SIA KeyManagement: Default encryption key (SIA_DEFAULT_KEY_HEX) is not configured.');

            return null; // Cannot proceed without key
        }
        if (empty($cipher)) {
            Log::error('SIA KeyManagement: Default cipher (SIA_DEFAULT_CIPHER) is not configured.');

            return null; // Cannot proceed without cipher
        }

        $binaryKey = @hex2bin($keyHex);
        if ($binaryKey === false) {
            Log::error('SIA KeyManagement: Configured default key is not valid hexadecimal.', ['key_hex_preview' => substr($keyHex, 0, 10).'...']);

            return null;
        }

        // Optional: Validate key length against the retrieved cipher *here* in the KMS if desired
        if (! $this->validateKeyLengthForCipher($binaryKey, $cipher, $panelAccountNumber)) {
            return null; // Error logged within validation method
        }

        return [$binaryKey, $cipher];
    }

    /**
     * Internal helper to validate key length against cipher.
     * Could be moved to a shared trait or utility if needed elsewhere.
     */
    private function validateKeyLengthForCipher(string $binaryKey, string $cipher, string $panelAccountNumber): bool
    {
        $expectedKeyLengthBytes = match (strtolower($cipher)) {
            'aes-128-cbc' => 16,
            'aes-192-cbc' => 24,
            'aes-256-cbc' => 32,
            default => null,
        };

        if ($expectedKeyLengthBytes === null) {
            Log::warning("SIA KeyManagement: Cannot validate key length for unknown cipher '{$cipher}'.", ['account' => $panelAccountNumber]);

            // Decide whether to allow unknown ciphers. Returning true allows it.
            return true;
        }

        if (strlen($binaryKey) !== $expectedKeyLengthBytes) {
            Log::error('SIA KeyManagement: Retrieved key length does not match cipher requirements.', [
                'cipher' => $cipher,
                'expected_length_bytes' => $expectedKeyLengthBytes,
                'actual_length_bytes' => strlen($binaryKey),
                'account' => $panelAccountNumber,
            ]);

            return false;
        }

        return true;
    }
}
