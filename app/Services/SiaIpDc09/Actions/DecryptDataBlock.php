<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Contracts\DecryptionService;
use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
     *                     or null on failure (decryption, padding error).
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

        $cipher = strtolower(Config::get('SiaIpDc09.encryption.default_cipher', 'aes-128-cbc'));

        $binaryKey = $this->keyManagementService->getKey(
            $panelAccountNumber,
            $receiverNumber,
            $linePrefix,
            $cipher
        );

        if ($binaryKey === null) {
            Log::error('SIA Decryption: Failed to retrieve decryption key.', $logContext);

            return null;
        }
        $logContext['cipher_used'] = $cipher;

        $encryptedBytes = @hex2bin($encryptedHexData);
        if ($encryptedBytes === false) {
            Log::error('SIA Decryption: Encrypted data is not valid hexadecimal.', $logContext);

            return null;
        }

        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false || $ivLength <= 0) {
            Log::error("SIA Decryption: Could not determine IV length for cipher '{$cipher}'.", $logContext);

            return null;
        }
        $iv = str_repeat("\0", $ivLength);

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

            // Validate decrypted block length
            $blockSize = Config::get('SiaIpDc09.encryption.block_size', 16);
            if (strlen($decryptedWithPadding) % $blockSize !== 0) {
                Log::error("SIA Decryption: Decrypted data length is not a multiple of block size ({$blockSize}).", $logContext + ['decrypted_length' => strlen($decryptedWithPadding)]);

                return null;
            }

            // Unpad the data
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
     * SIA padding scheme is: PaddingBytes + "|" + ActualDataAndTimestamp
     *
     * @param  string  $decryptedDataWithPadding  The raw output from openssl_decrypt (using OPENSSL_RAW_DATA).
     * @param  array  $logContextForError  Logging context.
     * @return string|null The unpadded data (content after "|"), or null if pad separator is not found.
     */
    private function unpadSiaData(string $decryptedDataWithPadding, array $logContextForError): ?string
    {
        $padSeparatorPos = strpos($decryptedDataWithPadding, self::PAD_SEPARATOR);

        if ($padSeparatorPos === false) {
            Log::error("SIA Decryption Unpadding: Decrypted data block missing mandatory pad separator '|'. Possible decryption or padding scheme error.", $logContextForError + [
                'decrypted_preview_hex' => bin2hex(substr($decryptedDataWithPadding, 0, 50)),
            ]);

            return null;
        }

        // The actual content is everything *after* the '|'
        $actualContent = substr($decryptedDataWithPadding, $padSeparatorPos + 1);

        // Optional: Log padding information if needed for debugging
        // $padding = substr($decryptedDataWithPadding, 0, $padSeparatorPos);
        // Log::debug("SIA Unpadding successful.", $logContextForError + ['padding_length' => strlen($padding), 'actual_content_length' => strlen($actualContent)]);

        return $actualContent;
    }
}
