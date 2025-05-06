<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Data;

use Carbon\CarbonImmutable;

readonly class ParsedContentDto
{
    /**
     * @param  string  $messageData  The content found within the primary data brackets `[...]`.
     * @param  array<string, string>  $extendedData  Associative array of extended data fields.
     * @param  string|null  $rawSiaTimestamp  The raw SIA timestamp string from the message (e.g., "_HH:MM:SS,MM-DD-YYYY"), if present.
     * @param  CarbonImmutable|null  $siaTimestamp  The parsed CarbonImmutable object for the SIA timestamp (in UTC), if present and valid.
     */
    public function __construct(
        public string $messageData,
        public array $extendedData,
        public ?string $rawSiaTimestamp, // RENAMED
        public ?CarbonImmutable $siaTimestamp, // RENAMED
    ) {}
}
