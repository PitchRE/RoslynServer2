<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Enums;

use Illuminate\Support\Facades\Config;

// We can add Config facade usage later when integrating with handlers
// use Illuminate\Support\Facades\Config;

enum SiaToken: string
{
    // --- Standard Event/Alarm Formats (Base Tokens) ---
    case ADM_CID = 'ADM-CID'; // Ademco Contact ID
    case SIA_DCS = 'SIA-DCS'; // SIA DC-04 Digital Communication Standard (Text Format)

    case ADM_CIDX = "ADM-CIDX"; // Ademco Contact ID Extended

    // --- Supervision / System Messages (Base Tokens) ---
    case NULL = 'NULL';       // Null message (Link Test)
    // case XNM = 'XNM';         // Extended Null Message

    // If you add more tokens for specific alarm formats (DC-07 Appendix H)
    // case ACR_SF = 'ACR-SF'; // Acron Super Fast
    // case FBI_SF = 'FBI-SF'; // FBI Super Fast
    // etc.

    /**
     * Tries to create an Enum instance from a string value parsed from the message,
     * and sets a flag indicating if the token was prefixed with '*' for encryption.
     *
     * @param  string  $parsedTokenString  The full token string from the message (e.g., "ADM-CID", "*SIA-DCS").
     * @param  bool  &$wasEncrypted  Passed by reference. Will be set to true if '*' was present, false otherwise.
     * @return static|null Returns the Enum case for the base token, or null if the base token value doesn't match any defined case.
     */
    public static function tryFromWithEncryptionFlag(string $parsedTokenString, bool &$wasEncrypted): ?static
    {
        $wasEncrypted = str_starts_with($parsedTokenString, '*');
        $baseTokenValue = $wasEncrypted ? substr($parsedTokenString, 1) : $parsedTokenString;

        // Use PHP 8.1's Enum::tryFrom method
        return self::tryFrom($baseTokenValue);
    }

    /**
     * Checks if a given base token string (without '*') is defined in this Enum.
     * This is useful for validating the token part after stripping the optional encryption marker.
     *
     * @param  string  $baseTokenValue  The token string without any '*' prefix.
     */
    public static function isBaseTokenDefined(string $baseTokenValue): bool
    {
        return self::tryFrom($baseTokenValue) !== null;
    }

    public function getInterpreterHandlerClass(): ?string
    {
        // Config path could be something like 'sia.token_handlers.ADM-CID'
        return Config::get('sia.token_handlers.' . $this->value);
    }
}
