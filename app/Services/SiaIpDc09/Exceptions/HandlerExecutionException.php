<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Models\SiaDc09Message;
use Throwable;

class HandlerExecutionException extends SiaProcessingException
{
    /**
     * @param  string  $handlerClass  The FQCN of the handler that failed.
     * @param  Throwable  $previous  The original exception thrown by the handler (MUST be provided).
     */
    /** @phpstan-ignore constructor.unusedParameter */
    public function __construct(
        string $message, // Custom message like "Error executing handler X for token Y"
        SiaDc09Message $siaMessageModel,
        string $handlerClass,
        Throwable $previous, // Original exception from the handler
        int $code = 0
        // No $additionalContext needed here as $previous captures the core issue
    ) {
        // Construct the main message, including the original error message
        $detailedMessage = "Error executing handler {$handlerClass} for token '{$siaMessageModel->protocol_token}': ".$previous->getMessage();

        $additionalContext = ['failed_handler_class' => $handlerClass];
        parent::__construct($detailedMessage, $siaMessageModel, $additionalContext, $code, $previous);
    }

    public function getFailedHandlerClass(): string
    {
        return $this->context()['failed_handler_class'];
    }
}
