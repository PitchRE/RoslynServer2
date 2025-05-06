<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface CrcCalculator
{
    /**
     * Calculates the checksum for the given binary data string.
     *
     * @param  string  $data  The binary data string to checksum.
     * @return string The calculated CRC as a fixed-length uppercase hexadecimal string.
     */
    public function handle(string $data): string; // Changed from calculate to handle

    /**
     * Gets the expected hex string length for this CRC variant.
     * This might not be part of the "handle" action directly but can be a static method
     * or a separate method on the action instance if needed.
     * For simplicity, let's keep it as a separate method on the instance.
     *
     * @return int The number of hexadecimal characters in the output string (e.g., 4 for CRC-16).
     */
    public function getHexStringLength(): int;
}
