<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Models\SiaDc09Message;
use Throwable;

class UnknownTokenException extends SiaProcessingException
{
    public function __construct(
        string $message,
        SiaDc09Message $siaMessageModel, // Model would have the unknown token stored
        string $unknownTokenValue,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $additionalContext = ['unknown_token_value' => $unknownTokenValue];
        parent::__construct($message, $siaMessageModel, $additionalContext, $code, $previous);
    }

    public function getUnknownTokenValue(): string
    {
        return $this->context()['unknown_token_value'];
    }
}
