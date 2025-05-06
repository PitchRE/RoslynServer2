<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Exceptions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use Throwable;

class MessageProcessingStateException extends SiaProcessingException
{
    public function __construct(
        string $message,
        SiaDc09Message $siaMessageModel,
        ProcessingStatus $expectedStatus,
        ProcessingStatus $actualStatus,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $additionalContext = [
            'expected_status' => $expectedStatus->value,
            'actual_status' => $actualStatus->value,
        ];
        parent::__construct($message, $siaMessageModel, $additionalContext, $code, $previous);
    }

    public function getExpectedStatusValue(): string
    {
        return $this->context()['expected_status'];
    }

    public function getActualStatusValue(): string
    {
        return $this->context()['actual_status'];
    }
}
