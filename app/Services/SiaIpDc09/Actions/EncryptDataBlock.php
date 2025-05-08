<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Services\SiaIpDc09\Contracts\EncryptionService;
use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EncryptDataBlock implements EncryptionService
{
    private const SIA_DATA_SEPARATOR = '|';

    private const DISALLOWED_PAD_CHARS_ASCII = [124, 91, 93];

    private KeyManagementService $keyManagementService;

    public function __construct(KeyManagementService $keyManagementService)
    {
        $this->keyManagementService = $keyManagementService;
    }

    public function handle(
        string $actualContentToEncrypt,
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?string {
        $logContext = [
            'panel_account_number' => $panelAccountNumber,
            'receiver_number' => $receiverNumber,
            'line_prefix' => $linePrefix,
            'original_content_preview' => substr($actualContentToEncrypt, 0, 50),
        ];

        $keyAndCipher = $this->keyManagementService->getKeyAndCipher(
            $panelAccountNumber,
            $receiverNumber,
            $linePrefix
        );

        // CORRECTED Validation: Only need to check if null was returned
        if ($keyAndCipher === null) {
            Log::error('SIA Encryption: Failed to retrieve valid key and cipher from KeyManagementService.', $logContext);

            // KMS implementation should have logged the specific reason (missing config, bad hex, bad length)
            return null;
        }

        // If not null, we know it's array{0: string, 1: string}
        [$binaryKey, $cipher] = $keyAndCipher;
        $logContext['cipher_used'] = $cipher;

        // 2. Get Block Size
        $blockSize = $this->getBlockSizeForCipher($cipher);
        if ($blockSize === null) {
            Log::error("SIA Encryption: Could not determine block size for unknown cipher '{$cipher}'.", $logContext);

            return null;
        }

        // 3. Generate Full Padded Plaintext Block
        try {
            $plainTextBlock = $this->generatePaddedPlaintextBlock( // Using the corrected method name from your snippet
                $actualContentToEncrypt,
                $blockSize
            );
            $logContext['plaintext_block_length'] = strlen($plainTextBlock);
        } catch (RuntimeException|Exception $e) { // Catch specific expected exceptions
            Log::critical('SIA Encryption: Failed to generate padded plaintext block.', $logContext + ['error' => $e->getMessage()]);

            // Do not proceed if padding fails
            return null;
        } catch (\Throwable $e) { // Catch any other unexpected errors during padding
            Log::critical('SIA Encryption: Unexpected error during padding generation.', $logContext + ['error' => $e->getMessage()]);

            return null;
        }

        // 4. Sanity check block length (This should ideally never fail if padding logic is correct)
        if (strlen($plainTextBlock) % $blockSize !== 0) {
            Log::critical("SIA Encryption: Generated plaintext block length ({$logContext['plaintext_block_length']}) is not multiple of block size ({$blockSize}). Padding logic error?", $logContext);

            // This indicates a bug in generatePaddedPlaintextBlock, treat as critical failure
            return null; // Return null instead of throwing RuntimeException to align with other failure paths
        }

        // 5. Determine IV
        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false || $ivLength <= 0) {
            Log::error("SIA Encryption: Could not determine IV length for cipher '{$cipher}'.", $logContext);

            return null;
        }
        $iv = str_repeat("\0", $ivLength);

        // 6. Perform encryption
        try {
            $encryptedBytes = openssl_encrypt(
                data: $plainTextBlock,          // Correct argument
                cipher_algo: $cipher,           // Correct argument
                passphrase: $binaryKey,         // Correct argument
                options: OPENSSL_RAW_DATA,      // Correct argument
                iv: $iv                         // Correct argument
            );

            if ($encryptedBytes === false) {
                $openSslError = '';
                while ($msg = openssl_error_string()) {
                    $openSslError .= $msg.' | ';
                }
                Log::error('SIA Encryption: openssl_encrypt failed.', $logContext + ['openssl_error' => rtrim($openSslError, ' | ')]);

                return null; // Return null on encryption failure
            }

            // 7. Return hex encoded result
            return bin2hex($encryptedBytes);

        } catch (\Throwable $e) {
            Log::critical('SIA Encryption: Unexpected exception during encryption call.', $logContext + [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_trace_snippet' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return null; // Return null on unexpected exception
        }
    }

    // Method renamed based on your snippet
    private function generatePaddedPlaintextBlock(string $actualContent, int $blockSize): string
    {
        if ($blockSize <= 0) {
            throw new RuntimeException("Invalid block size ({$blockSize}) provided for padding calculation.");
        }
        $lengthOfSeparatorAndContent = 1 + strlen($actualContent);
        $remainder = $lengthOfSeparatorAndContent % $blockSize;
        $paddingLengthNeeded = ($remainder === 0) ? $blockSize : $blockSize - $remainder;

        $paddingBytes = '';
        for ($i = 0; $i < $paddingLengthNeeded; $i++) {
            $randomByteValue = null;
            do {
                $byteValue = random_int(0, 255); // Can throw Exception
                if (! in_array($byteValue, self::DISALLOWED_PAD_CHARS_ASCII, true)) {
                    $randomByteValue = $byteValue;
                }
            } while ($randomByteValue === null);
            $paddingBytes .= chr($randomByteValue);
        }

        return $paddingBytes.self::SIA_DATA_SEPARATOR.$actualContent;
    }

    private function getBlockSizeForCipher(string $cipher): ?int
    {
        if (str_starts_with(strtolower($cipher), 'aes-')) {
            return 16;
        }

        return null;
    }
}
