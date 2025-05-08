<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Contracts\DecryptionService;
use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log; // Keep Config for block_size fallback if needed

class DecryptDataBlock implements DecryptionService
{
    private KeyManagementService $keyManagementService;

    private const PAD_SEPARATOR = '|';

    public function __construct(KeyManagementService $keyManagementService)
    {
        $this->keyManagementService = $keyManagementService;
    }

    /**
     * Decrypts hex-encoded SIA DC-09 encrypted data and removes SIA-specific padding.
     *
     * @param  string  $encryptedHexData  The hex-encoded string of encrypted data.
     * @param  string  $panelAccountNumber  The panel's account number (without '#').
     * @param  string|null  $receiverNumber  Optional receiver number.
     * @param  string|null  $linePrefix  Optional line prefix.
     * @return string|null The decrypted and unpadded plaintext data (content after '|'),
     *                     or null on failure (key/cipher retrieval, decryption, padding error).
     */
    public function handle(
        string $encryptedHexData,
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?string {
        $logContext = [
            'panel_account_number' => $panelAccountNumber,
            'receiver_number' => $receiverNumber,
            'line_prefix' => $linePrefix,
            'encrypted_hex_preview' => substr($encryptedHexData, 0, 64).(strlen($encryptedHexData) > 64 ? '...' : ''),
        ];

        $keyAndCipher = $this->keyManagementService->getKeyAndCipher(
            $panelAccountNumber,
            $receiverNumber,
            $linePrefix
        );

        // CORRECTED Validation: Only need to check if null was returned
        if ($keyAndCipher === null) {
            Log::error('SIA Decryption: Failed to retrieve valid key and cipher from KeyManagementService.', $logContext);

            // KMS implementation should have logged the specific reason
            return null;
        }

        // If not null, we know it's array{0: string, 1: string}
        [$binaryKey, $cipher] = $keyAndCipher;
        $logContext['cipher_used'] = $cipher;

        // KMS is assumed to have validated key length against cipher already.

        // 2. Convert hex-encoded encrypted data to binary
        $encryptedBytes = hex2bin($encryptedHexData);
        if ($encryptedBytes === false) {
            Log::error('SIA Decryption: Encrypted data is not valid hexadecimal.', $logContext);

            return null;
        }

        // 3. Determine IV length and create all-zero IV
        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false || $ivLength <= 0) {
            // Log error if KMS provided a cipher for which IV length can't be determined
            Log::error("SIA Decryption: Could not determine IV length for cipher '{$cipher}' provided by KMS.", $logContext);

            return null;
        }
        $iv = str_repeat("\0", $ivLength);

        // 4. Perform decryption
        try {
            $decryptedWithPadding = openssl_decrypt(
                data: $encryptedBytes,
                cipher_algo: $cipher,
                passphrase: $binaryKey,
                options: OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                iv: $iv
            );

            if ($decryptedWithPadding === false) {
                $openSslError = '';
                while ($msg = openssl_error_string()) {
                    $openSslError .= $msg.' | ';
                }
                Log::error('SIA Decryption: openssl_decrypt failed.', $logContext + ['openssl_error' => rtrim($openSslError, ' | ')]);

                return null;
            }

            // 5. Validate decrypted block length
            $blockSize = $this->getBlockSizeForCipher($cipher); // Use helper
            if ($blockSize === null) {
                Log::error("SIA Decryption: Could not determine block size for cipher '{$cipher}' to validate length.", $logContext);

                return null; // Cannot proceed safely
            }
            if (strlen($decryptedWithPadding) % $blockSize !== 0) {
                Log::error("SIA Decryption: Decrypted data length is not a multiple of block size ({$blockSize}). Possible data corruption or encryption error.", $logContext + ['decrypted_length' => strlen($decryptedWithPadding)]);

                return null; // Treat as failure
            }

            // 6. Unpad the data using internal helper
            return $this->unpadSiaData($decryptedWithPadding, $logContext);

        } catch (\Throwable $e) {
            Log::critical('SIA Decryption: Unexpected exception during decryption or unpadding process.', $logContext + [
                'exception_message' => $e->getMessage(),
                'exception_trace_snippet' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return null;
        }
    }

    /**
     * Removes SIA-specific padding from the decrypted data.
     */
    private function unpadSiaData(string $decryptedDataWithPadding, array $logContextForError): ?string
    {
        $padSeparatorPos = strpos($decryptedDataWithPadding, self::PAD_SEPARATOR);

        if ($padSeparatorPos === false) {
            Log::error("SIA Decryption Unpadding: Decrypted data block missing mandatory pad separator '|'.", $logContextForError + [
                'decrypted_preview_hex' => bin2hex(substr($decryptedDataWithPadding, 0, 50)),
            ]);

            return null;
        }

        // Return content after the separator
        return substr($decryptedDataWithPadding, $padSeparatorPos + 1);
    }

    /**
     * Helper to get block size for common AES ciphers.
     */
    private function getBlockSizeForCipher(string $cipher): ?int
    {
        if (str_starts_with(strtolower($cipher), 'aes-')) {
            return 16;
        }

        // Add other ciphers if necessary
        return null;
    }
}
