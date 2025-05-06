<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Contracts;

interface DecryptionService // Or DecryptionServiceInterface
{
    /**
     * Decrypts hex-encoded SIA DC-09 encrypted data.
     *
     * Expects AES in Cipher Block Chaining (CBC) mode with an all-zero
     * Initialization Vector (IV). Input is hex-encoded.
     *
     * @param  string  $encryptedHexData  The hex-encoded string of encrypted data.
     * @param  string  $panelAccountNumber  The panel's account number (without '#'), used for potential key lookup.
     * @param  string|null  $receiverNumber  Optional receiver number, for potential key lookup.
     * @param  string|null  $linePrefix  Optional line prefix, for potential key lookup.
     * @return string|null The decrypted plaintext data, or null on failure (e.g., key not found, decryption error, invalid hex).
     */
    public function handle(
        string $encryptedHexData,
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?string;
}
