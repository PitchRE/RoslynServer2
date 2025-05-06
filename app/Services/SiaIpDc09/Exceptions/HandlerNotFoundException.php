<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Enums\SiaToken;
use Throwable;

class HandlerNotFoundException extends SiaProcessingException
{
    public function __construct(
        string $message,
        SiaDc09Message $siaMessageModel, // Model contains the token
        SiaToken $tokenEnumCase, // The enum case for which handler was sought
        ?string $configuredHandlerClass, // The class name from config (might be null/invalid)
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $additionalContext = [
            'token_enum_case' => $tokenEnumCase->value,
            'configured_handler_class' => $configuredHandlerClass,
        ];
        parent::__construct($message, $siaMessageModel, $additionalContext, $code, $previous);
    }

    public function getTokenEnumValue(): string
    {
        return $this->context()['token_enum_case'];
    }

    public function getConfiguredHandlerClass(): ?string
    {
        return $this->context()['configured_handler_class'] ?? null;
    }
}
