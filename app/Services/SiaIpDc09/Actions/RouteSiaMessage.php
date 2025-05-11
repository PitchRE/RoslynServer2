<?php

namespace App\Services\SiaIpDc09\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use App\Models\SiaDc09Message;
use App\Services\AlarmDataFormats\Actions\EnrichSecurityEventWithRelations;
use App\Services\SiaIpDc09\Enums\ProcessingStatus as SiaMessageProcessingStatus;
use App\Services\SiaIpDc09\Enums\SiaToken;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsJob;
use Throwable;

class RouteSiaMessage
{
    use AsAction;
    use AsJob;

    public SiaDc09Message $siaMessage;

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(SiaDc09Message $siaMessage): ?SecurityEvent
    {
        $this->siaMessage = $siaMessage;

        // Initial eligibility check (ensure it's PARSED and not already fully processed)
        if (!in_array($this->siaMessage->processing_status, SiaMessageProcessingStatus::eligibleForRouting())) { // Assuming eligibleForRouting includes PARSED
            Log::info('RouteSiaMessage: SiaDc09Message not in an eligible state for routing.', [
                'sia_message_id' => $this->siaMessage->id,
                'status' => $this->siaMessage->processing_status->value,
            ]);

            return null;
        }
        if (in_array($this->siaMessage->processing_status, SiaMessageProcessingStatus::successfulOutcomes())) {
            Log::info('RouteSiaMessage: SiaDc09Message already successfully processed, skipping.', [
                'sia_message_id' => $this->siaMessage->id,
                'status' => $this->siaMessage->processing_status->value,
            ]);

            return SecurityEvent::where('source_message_type', SiaDc09Message::class)
                ->where('source_message_id', $this->siaMessage->id)
                ->where('event_category', '!=', SecurityEventCategory::CSR_PROCESSING_ERROR)
                ->first();
        }

        /** @var SecurityEvent|null $interpretedEventOrNull */ // Renamed to clarify it can be null for NULL token
        $interpretedEventOrNull = null;
        $handlerClass = null;
        $processingStage = 'initialization';
        $baseProtocolToken = $this->siaMessage->protocol_token;

        try {
            // Optional: Update status to indicate processing has started on this attempt
            // $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSING_IN_PROGRESS, 'Routing and interpretation started.');
            $processingStage = 'token_validation';

            $siaTokenEnum = SiaToken::tryFrom($baseProtocolToken ?? '');

            if (!$siaTokenEnum) {
                $errorMessage = "Unknown protocol token: {$baseProtocolToken}";
                Log::warning('RouteSiaMessage: Unknown base protocol token.', ['sia_message_id' => $this->siaMessage->id, 'base_protocol_token' => $baseProtocolToken]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::TOKEN_UNKNOWN, $errorMessage);
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, $errorMessage, ['protocol_token' => $baseProtocolToken]);

                return null;
            }

            $processingStage = 'handler_resolution';
            $handlerClass = $siaTokenEnum->getInterpreterHandlerClass();

            if (!$handlerClass || !class_exists($handlerClass)) {
                $errorMessage = "No handler configured or class does not exist for token: {$siaTokenEnum->value}";
                Log::error('RouteSiaMessage: Handler class issue.', ['sia_message_id' => $this->siaMessage->id, 'token' => $siaTokenEnum->value, 'handler' => $handlerClass ?? 'Not Configured']);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::TOKEN_HANDLER_MISSING, $errorMessage);
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, $errorMessage, ['protocol_token' => $siaTokenEnum->value]);

                return null;
            }

            $processingStage = 'interpretation';
            // Note: The ::run method for AsAction returns the result of the handle method.
            // HandleSiaNullMessage->handle() will return null.
            // InterpretAdemcoContactIdData->handle() will return ?SecurityEvent.
            $interpretedEventOrNull = $handlerClass::run(
                rawMessageData: $this->siaMessage->message_data,
                occurredAt: $this->siaMessage->sia_timestamp ?? CarbonImmutable::instance($this->siaMessage->created_at),
                csrDeviceIdentifier: $this->siaMessage->panel_account_number,
                sourceMessage: $this->siaMessage
            );

            // Handle case where handler (like HandleSiaNullMessage) correctly processes but returns null (no SecurityEvent needed)
            if ($interpretedEventOrNull === null && $siaTokenEnum === SiaToken::NULL) {
                Log::info('RouteSiaMessage: NULL message processed by handler.', ['sia_message_id' => $this->siaMessage->id, 'handler_class' => $handlerClass]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSED_NULL_MESSAGE, 'NULL message handled.'); // Use your enum

                return null; // Explicitly return null, no SecurityEvent to create
            }

            // Check if interpreter returned a valid SecurityEvent object
            if (!$interpretedEventOrNull instanceof SecurityEvent) {
                $errorMessage = "Interpreter class '{$handlerClass}' returned invalid data or null for a non-NULL token.";
                Log::error('RouteSiaMessage: Interpreter did not return a SecurityEvent instance for a token that expects one.', [
                    'sia_message_id' => $this->siaMessage->id,
                    'handler_class' => $handlerClass,
                    'token' => $siaTokenEnum->value,
                    'returned_type' => is_object($interpretedEventOrNull) ? get_class($interpretedEventOrNull) : gettype($interpretedEventOrNull),
                ]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::INTERPRETATION_FAILED, $errorMessage);
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, $errorMessage, ['handler_class' => $handlerClass]);

                return null;
            }

            // At this point, $interpretedEventOrNull is a valid SecurityEvent instance
            $securityEventToProcess = $interpretedEventOrNull;

            if (!$securityEventToProcess->sourceMessage()->exists()) { // Ensure association if interpreter didn't do it
                $securityEventToProcess->sourceMessage()->associate($this->siaMessage);
            }

            $finalSecurityEvent = DB::transaction(function () use ($securityEventToProcess, &$processingStage) {
                $processingStage = 'enrichment';
                $enrichedEvent = EnrichSecurityEventWithRelations::run($securityEventToProcess);

                $processingStage = 'saving_security_event';
                $enrichedEvent->save();

                $processingStage = 'updating_sia_message_status_success';
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSED_EVENT_CREATED, "SecurityEvent ID: {$enrichedEvent->id} created.");

                return $enrichedEvent;
            });

            Log::info('RouteSiaMessage: SecurityEvent processed successfully.', ['security_event_id' => $finalSecurityEvent->id, 'sia_message_id' => $this->siaMessage->id]);

            return $finalSecurityEvent;

        } catch (Throwable $e) {
            // ... (existing catch block logic - no change needed here for NULL handling) ...
            Log::critical('RouteSiaMessage: Unhandled exception during signal processing.', [
                'sia_message_id' => $this->siaMessage->id,
                'base_protocol_token' => $baseProtocolToken,
                'handler_class_attempted' => $handlerClass ?? 'Not determined',
                'processing_stage_at_failure' => $processingStage,
                'error_message' => $e->getMessage(),
                'error_trace_snippet' => Str::limit($e->getTraceAsString(), 1000),
            ]);
            $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSING_UNEXPECTED_ERROR, "Unexpected error at stage '{$processingStage}': " . Str::limit($e->getMessage(), 150));

            $errorEventType = SecurityEventType::PROCESSING_INTERPRETATION_FAILURE;
            if ($processingStage === 'enrichment') {
                $errorEventType = SecurityEventType::PROCESSING_ENRICHMENT_FAILURE;
            } elseif ($processingStage === 'saving_security_event' || $processingStage === 'updating_sia_message_status_success') {
                $errorEventType = SecurityEventType::PROCESSING_SAVE_FAILURE;
            }

            $this->createProcessingErrorEvent(
                $errorEventType,
                "Processing error at '{$processingStage}' for signal: " . Str::limit($e->getMessage(), 100),
                ['original_error_message' => $e->getMessage(), 'stage' => $processingStage]
            );

            // throw $e; // Optional: rethrow for job failure
            return null;
        }
    }

    // ... (updateSiaMessageStatus, createProcessingErrorEvent, getCsrDeviceIdentifierFromSiaMessage methods remain the same) ...
    protected function updateSiaMessageStatus(SiaMessageProcessingStatus $status, string $notes): void
    {
        if ($this->siaMessage->exists) {
            $this->siaMessage->processing_status = $status;
            $this->siaMessage->processing_notes = trim(($this->siaMessage->processing_notes ? $this->siaMessage->processing_notes . "\n" : '') . $notes);
            $this->siaMessage->saveQuietly();
        } elseif ($this->siaMessage) {
            Log::warning('RouteSiaMessage: Attempted to update status on a non-persisted SiaDc09Message instance during error handling.');
        }
    }

    protected function createProcessingErrorEvent(SecurityEventType $errorType, string $description, array $details = []): void
    {
        try {
            DB::transaction(function () use ($errorType, $description, $details) {
                $errorEvent = new SecurityEvent([
                    'occurred_at' => CarbonImmutable::now(),
                    'received_at' => CarbonImmutable::now(),
                    'source_protocol' => 'CSR_INTERNAL',
                    'raw_event_code' => $errorType->value,
                    'raw_event_description' => $description,
                    'raw_account_identifier' => $this->siaMessage->panel_account_number ?? 'UNKNOWN_ACCOUNT',
                    'raw_device_identifier' => $this->getCsrDeviceIdentifierFromSiaMessage($this->siaMessage) ?? ($this->siaMessage->panel_account_number ?? 'UNKNOWN_DEVICE'),
                    'event_category' => SecurityEventCategory::CSR_PROCESSING_ERROR,
                    'event_type' => $errorType,
                    'event_qualifier' => SecurityEventQualifier::ACTIVATION,
                    'priority' => 5,
                    'normalized_description' => "CSR PROCESSING ERROR: {$description} - SIA Msg ID: {$this->siaMessage->id}",
                    'message_details' => json_encode(array_merge($details, ['sia_message_id' => $this->siaMessage->id, 'sia_raw_frame_hex' => $this->siaMessage->raw_frame_hex])),
                    'status' => SecurityEventStatus::NEW ,
                ]);

                if ($this->siaMessage->exists) {
                    $errorEvent->sourceMessage()->associate($this->siaMessage);
                }
                $errorEvent = EnrichSecurityEventWithRelations::run($errorEvent);
                $errorEvent->save();
                Log::info('RouteSiaMessage: Processing error SecurityEvent created.', ['error_event_id' => $errorEvent->id, 'original_sia_id' => $this->siaMessage->id]);
            });
        } catch (Throwable $e) {
            Log::critical('RouteSiaMessage: CRITICAL - Failed to create AND SAVE processing error notification event!', [
                'original_sia_id' => $this->siaMessage->id,
                'error_creating_error_event' => $e->getMessage(),
                'original_processing_error_type' => $errorType->value,
                'original_processing_description' => $description,
            ]);
        }
    }

    protected function getCsrDeviceIdentifierFromSiaMessage(SiaDc09Message $siaMessage): ?string
    {
        if (!empty($siaMessage->panel_account_number)) {
            return $siaMessage->panel_account_number;
        }

        return null;
    }
}
