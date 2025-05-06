<?php

declare(strict_types=1); // Enforce strict types

namespace App\Services\SiaIpDc09\Data;

readonly class ValidatedFrameDto
{
    /**
     * @param  string  $rawBody  The binary message body (content after CRC and Length headers, before final <CR>).
     * @param  string  $crcHeader  The 4-character hexadecimal CRC string from the frame header.
     * @param  int  $lengthHeaderValue  The integer value of the body length specified in the '0LLL' header.
     * @param  string  $rawFrame  The complete raw binary frame (CRC+Length+Body) received, for context in exceptions.
     */
    public function __construct(
        public string $rawBody,
        public string $crcHeader,
        public int $lengthHeaderValue,
        public string $rawFrame,
    ) {}
}
