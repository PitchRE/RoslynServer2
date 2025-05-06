<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Contracts;

interface KeyManagementService // Or KeyManagementServiceInterface
{
    /**
     * Retrieves the appropriate encryption/decryption key in its raw binary format.
     *
     * The key retrieval logic might depend on the panel account number,
     * receiver number, line prefix, or other contextual information.
     *
     * @param  string  $panelAccountNumber  The panel's account number (without '#').
     * @param  string|null  $receiverNumber  Optional receiver number.
     * @param  string|null  $linePrefix  Optional line prefix.
     * @param  string  $cipher  The cipher being used (e.g., 'aes-128-cbc'), to potentially validate key length.
     * @return string|null The raw binary encryption key, or null if no suitable key is found.
     */
    public function getKey(
        string $panelAccountNumber,
        ?string $receiverNumber,
        ?string $linePrefix,
        string $cipher // To help determine expected key length or type
    ): ?string;
}
