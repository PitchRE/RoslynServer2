<?php

declare(strict_types=1);

namespace App\Support\Crc\Actions;

use App\Support\Crc\Contracts\CrcCalculator;
use Lorisleiva\Actions\Concerns\AsAction; // If you use this package

class CalculateCrc16CcittFalse implements CrcCalculator // Implement the contract
{
    use AsAction; // If you use this package for static run() method

    private const POLYNOMIAL = 0x1021;

    private const INITIAL_VALUE = 0xFFFF;

    private const HEX_STRING_LENGTH = 4; // CRC-16 results in 4 hex characters

    /**
     * Calculates the CRC-16/CCITT-FALSE checksum.
     * Poly: 0x1021, Init: 0xFFFF, No Refin, No Refout, XorOut: 0x0000.
     * This is the standard CRC used in SIA DC-07/DC-09 frames.
     */
    public function handle(string $data): string
    {
        $crc = self::INITIAL_VALUE;
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $byte = ord($data[$i]);
            $crc ^= ($byte << 8); // Bring byte into MSB of 16-bit CRC

            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) { // Check MSB
                    $crc = ($crc << 1) ^ self::POLYNOMIAL;
                } else {
                    $crc <<= 1;
                }
            }
        }

        $crc &= 0xFFFF; // Ensure it's a 16-bit value

        return $this->formatAsHex($crc);
    }

    public function getHexStringLength(): int
    {
        return self::HEX_STRING_LENGTH;
    }

    private function formatAsHex(int $crcInt): string
    {
        return str_pad(strtoupper(dechex($crcInt)), self::HEX_STRING_LENGTH, '0', STR_PAD_LEFT);
    }
}
