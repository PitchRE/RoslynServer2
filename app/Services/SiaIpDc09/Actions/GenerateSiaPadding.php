<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log; // For random_int failure

// If using lorisleiva/laravel-actions:
// use Lorisleiva\Actions\Concerns\AsAction;

class GenerateSiaPadding
{
    // If using lorisleiva/laravel-actions:
    // use AsAction;

    // Define characters disallowed in padding (Spec 5.4.4.2)
    // ASCII values: "|" (124, x7C), "[" (91, x5B), "]" (93, x5D)
    private const DISALLOWED_PAD_CHARS_ASCII = [124, 91, 93];

    /**
     * Generates pseudo-random padding for SIA DC-09 encrypted messages.
     *
     * Ensures the total length of (Padding + Data_Separator + Data_Itself)
     * is a multiple of the encryption block size (usually 16 for AES).
     * Adds a full block of padding if the content (Data_Separator + Data_Itself)
     * is already a multiple of the block size.
     *
     * The "Data_Itself" here would be the part like "[]_TIMESTAMP" for an encrypted ACK.
     * The "Data_Separator" is the "|" character.
     *
     * @param  int  $contentLengthAfterPadSeparator  The length (in bytes) of the content that will follow the pad separator '|'
     *                                               (e.g., length of "[]_HH:MM:SS,MM-DD-YYYY" for an ACK).
     * @return string The generated padding string (this string does NOT include the '|' itself).
     *                Returns an empty string if something goes wrong, though it should always generate padding.
     *
     * @throws Exception If secure random byte generation fails.
     */
    public function handle(int $contentLengthAfterPadSeparator): string
    {
        $blockSize = Config::get('SiaIpDc09.encryption.block_size', 16);

        // The total region to be encrypted is: Padding + "|" + ContentAfterPadSeparator
        // Let P be length of Padding. Let S be length of ContentAfterPadSeparator.
        // We need (P + 1 + S) to be a multiple of $blockSize.
        // (P + 1 + S) = k * $blockSize
        // P = k * $blockSize - 1 - S

        // Calculate length of (PadSeparator + ContentAfterPadSeparator)
        $lengthOfSeparatorAndContent = 1 + $contentLengthAfterPadSeparator;

        $remainder = $lengthOfSeparatorAndContent % $blockSize;
        $paddingLengthNeeded = ($remainder === 0) ? $blockSize : $blockSize - $remainder;

        // Spec 5.4.4.3: "When a message is already an even multiple of 16 bytes, 16 pad bytes shall be added..."
        // Our calculation: if $lengthOfSeparatorAndContent is a multiple, $remainder is 0, so $paddingLengthNeeded becomes $blockSize.
        // This ensures that even if ($contentLengthAfterPadSeparator + 1) was already a multiple of 16,
        // we still add a full block of padding. This makes the total P + 1 + S a multiple of $blockSize
        // where P itself is $blockSize.

        if ($paddingLengthNeeded <= 0 && $lengthOfSeparatorAndContent > 0) {
            // This case should ideally not be hit with the logic above unless $blockSize is misconfigured or very small.
            Log::warning('SIA Padding: Calculated zero or negative padding needed, but content exists. Defaulting to full block.', [
                'content_len_after_sep' => $contentLengthAfterPadSeparator,
                'block_size' => $blockSize,
            ]);
            $paddingLengthNeeded = $blockSize;
        } elseif ($lengthOfSeparatorAndContent === 0 && $paddingLengthNeeded === 0) {
            // If there's no content and no remainder, we need a full block of padding.
            // This is to ensure the encrypted block is never empty if we have to encrypt "nothing".
            $paddingLengthNeeded = $blockSize;
        }

        $paddingBytes = '';
        for ($i = 0; $i < $paddingLengthNeeded; $i++) {
            $randomByteValue = null;
            do {
                // Generate a random byte value (0-255)
                $byteValue = random_int(0, 255); // Throws Exception on failure

                // Check if it's one of the disallowed characters
                if (! in_array($byteValue, self::DISALLOWED_PAD_CHARS_ASCII, true)) {
                    $randomByteValue = $byteValue;
                }
            } while ($randomByteValue === null); // Loop until an allowed byte is found

            $paddingBytes .= chr($randomByteValue);
        }

        return $paddingBytes;
    }
}
