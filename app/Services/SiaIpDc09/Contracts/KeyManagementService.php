<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Contracts;

interface KeyManagementService
{
    /**
     * Retrieves the appropriate encryption/decryption key (binary) AND the corresponding cipher algorithm name.
     *
     * The retrieval logic might depend on the panel account number, receiver number, line prefix,
     * or other contextual information available to the implementation.
     *
     * @param  string  $panelAccountNumber  The panel's account number (without '#').
     * @param  string|null  $receiverNumber  Optional receiver number.
     * @param  string|null  $linePrefix  Optional line prefix.
     * @return array{?string, ?string}|null An array containing [binary key, cipher name] or null if not found.
     *                                      Example: ['\x01\x02...', 'aes-128-cbc']
     *                                      Returns null if no key/cipher configuration found for the identifiers.
     *                                      Individual elements in the array can be null if only one part is found (less ideal).
     */
    public function getKeyAndCipher(
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix
    ): ?array; // Return type is now an array [key, cipher] or null
}
