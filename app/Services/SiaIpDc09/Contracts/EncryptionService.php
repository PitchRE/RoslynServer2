<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Contracts;

interface EncryptionService // Or EncryptionServiceInterface
{
    /**
     * Encrypts plaintext data using settings appropriate for SIA DC-09.
     *
     * The SIA DC-09 standard specifies AES with Cipher Block Chaining (CBC) mode
     * and an all-zero Initialization Vector (IV). The encrypted output should
     * be hex-encoded for transmission.
     *
     * @param  string  $plainTextData  The raw data to encrypt (e.g., padding + '|' + data + timestamp).
     * @param  string  $panelAccountNumber  The panel's account number (without '#'), used for potential key lookup.
     * @param  string|null  $receiverNumber  Optional receiver number, for potential key lookup.
     * @param  string|null  $linePrefix  Optional line prefix, for potential key lookup.
     * @return string|null Hex-encoded encrypted data, or null on failure (e.g., key not found, encryption error).
     */
    public function handle(
        string $plainTextData,
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?string;
}
