<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Models\SiaDc09Message; // Assuming this is your Eloquent model
use RuntimeException;
use Throwable;

/**
 * Abstract base exception for errors occurring *after* initial SIA DC-09 frame parsing,
 * during data interpretation, routing, or subsequent business logic steps
 * related to the (successfully parsed) SIA message content.
 *
 * These exceptions are typically associated with a SiaDc09Message model instance.
 */
abstract class SiaProcessingException extends RuntimeException
{
    protected SiaDc09Message $siaMessageModel;

    /**
     * @var array<string, mixed> Holds structured context for logging.
     */
    protected array $exceptionContextData = [];

    /**
     * @param  string  $message  The primary error message.
     * @param  SiaDc09Message  $siaMessageModel  The Eloquent model instance being processed.
     * @param  array<string, mixed>  $additionalContext  Specific context from the subclass.
     */
    public function __construct(
        string $message,
        SiaDc09Message $siaMessageModel,
        array $additionalContext = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->siaMessageModel = $siaMessageModel;
        $this->buildExceptionContext($additionalContext);
    }

    protected function buildExceptionContext(array $additionalContext): void
    {
        $this->exceptionContextData = [
            'error_message' => $this->getMessage(),
            'exception_class' => static::class,
            'associated_message_id' => $this->siaMessageModel->id,
            'panel_account_number' => $this->siaMessageModel->panel_account_number,
            'protocol_token' => $this->siaMessageModel->protocol_token,
            'sequence_number' => $this->siaMessageModel->sequence_number,
            'current_processing_status' => $this->siaMessageModel->processing_status->value, // if status is an enum
        ];

        if ($this->getPrevious()) {
            $this->exceptionContextData['previous_exception_class'] = get_class($this->getPrevious());
            $this->exceptionContextData['previous_exception_message'] = $this->getPrevious()->getMessage();
        }

        $this->exceptionContextData = array_merge($this->exceptionContextData, $additionalContext);
        $this->exceptionContextData = array_filter($this->exceptionContextData);
    }

    public function getsiaMessageModel(): SiaDc09Message
    {
        return $this->siaMessageModel;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->exceptionContextData;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function addContext(array $context): self
    {
        $this->exceptionContextData = array_merge($this->exceptionContextData, $context);

        return $this;
    }
}
