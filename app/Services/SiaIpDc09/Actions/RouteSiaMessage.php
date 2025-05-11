<?php

namespace App\Services\SiaIpDc09\Actions;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use App\Models\SiaDc09Message;
use App\Services\AlarmDataFormats\Actions\EnrichSecurityEventWithRelations; // Assuming this is the correct path
use App\Services\SiaIpDc09\Enums\ProcessingStatus as SiaMessageProcessingStatus;
use App\Services\SiaIpDc09\Enums\SiaToken;
use Carbon\CarbonImmutable;
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

    public function handle(SiaDc09Message $siaMessage): ?SecurityEvent
    {
        $this->siaMessage = $siaMessage;

        if ($this->siaMessage->processing_status != SiaMessageProcessingStatus::PARSED) {
            Log::info('SiaDc09Message is not eligible for interpreting, skipping.', ['sia_message_id' => $this->siaMessage->id, 'status' => $this->siaMessage->processing_status->value]);

            return null;
        }

        // Variables to hold intermediate results and track failure stage
        /** @var SecurityEvent|null $interpretedEvent */
        $interpretedEvent = null;
        /** @var SecurityEvent|null $enrichedEvent */
        $enrichedEvent = null; // Initialize to null
        $handlerClass = null;
        $processingStage = 'interpretation'; // To track where failure occurred

        $baseProtocolToken = $this->siaMessage->protocol_token;

        try {
            $siaTokenEnum = SiaToken::tryFrom($baseProtocolToken ?? '');

            if (!$siaTokenEnum) {
                // ... (logging and error event creation for unsupported token - same as your code)
                Log::warning('RouteSiaMessage: Unknown or unsupported base protocol token.', [/* ... */]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSING_HANDLER_ERROR, "Unsupported protocol token: {$baseProtocolToken}");
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, "Unsupported signal protocol token: {$baseProtocolToken}", ['protocol_token' => $baseProtocolToken]);

                return null;
            }

            $handlerClass = $siaTokenEnum->getInterpreterHandlerClass();



            if (!$handlerClass || !class_exists($handlerClass)) {
                // ... (logging and error event creation for no handler - same as your code)
                Log::error('RouteSiaMessage: No handler class configured or class does not exist for token.', [/* ... */]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSING_HANDLER_ERROR, "No handler for token: {$siaTokenEnum->value}");
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, "No interpreter configured for signal protocol: {$siaTokenEnum->value}", ['protocol_token' => $siaTokenEnum->value]);

                return null;
            }

            $interpretedEvent = $handlerClass::run(
                rawMessageData: $this->siaMessage->message_data,
                occurredAt: $this->siaMessage->sia_timestamp ?? CarbonImmutable::instance($this->siaMessage->created_at),
                csrDeviceIdentifier: $this->getCsrDeviceIdentifierFromSiaMessage($this->siaMessage),
                sourceMessage: $this->siaMessage
            );

            if (!$interpretedEvent instanceof SecurityEvent) {
                // ... (logging and error event creation for invalid interpreter output - same as your code)
                Log::error('RouteSiaMessage: Interpreter did not return a SecurityEvent instance.', [/* ... */]);
                $this->updateSiaMessageStatus(SiaMessageProcessingStatus::INTERPRETATION_FAILED, "Interpreter class '{$handlerClass}' returned invalid data.");
                $this->createProcessingErrorEvent(SecurityEventType::PROCESSING_INTERPRETATION_FAILURE, "Interpreter for {$siaTokenEnum->value} returned invalid data.", ['handler_class' => $handlerClass]);

                return null;
            }

            $processingStage = 'enrichment';
            $enrichedEvent = EnrichSecurityEventWithRelations::run($interpretedEvent); // $interpretedEvent is passed by value, $enrichedEvent gets the result

            $processingStage = 'saving';
            $enrichedEvent->save(); // $enrichedEvent should be the same instance as $interpretedEvent, but modified

            Log::info('RouteSiaMessage: SecurityEvent created and saved successfully.', ['security_event_id' => $enrichedEvent->id, 'sia_message_id' => $this->siaMessage->id]);
            $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSED_SUCCESSFULLY, "SecurityEvent ID: {$enrichedEvent->id} created.");

            return $enrichedEvent;

        } catch (Throwable $e) {
            Log::critical('RouteSiaMessage: Unhandled exception during signal processing.', [
                'sia_message_id' => $this->siaMessage->id,
                'base_protocol_token' => $baseProtocolToken,
                'handler_class_attempted' => $handlerClass ?? 'Not determined',
                'processing_stage' => $processingStage, // Log at which stage it failed
                'error_message' => $e->getMessage(),
                'error_trace_snippet' => Str::limit($e->getTraceAsString(), 1000),
            ]);
            $this->updateSiaMessageStatus(SiaMessageProcessingStatus::PROCESSING_HANDLER_ERROR, 'Unexpected error: ' . Str::limit($e->getMessage(), 200));

            $errorEventType = SecurityEventType::PROCESSING_INTERPRETATION_FAILURE; // Default
            if ($processingStage === 'enrichment') {
                $errorEventType = SecurityEventType::PROCESSING_ENRICHMENT_FAILURE;
            } elseif ($processingStage === 'saving') {
                $errorEventType = SecurityEventType::PROCESSING_SAVE_FAILURE;
            }
            // If $interpretedEvent is an instance of SecurityEvent but $enrichedEvent isn't (or failed before save),
            // it implies an enrichment or saving failure.
            // If $interpretedEvent itself isn't a SecurityEvent, it's an interpretation failure.

            $this->createProcessingErrorEvent(
                $errorEventType,
                'Processing error for signal at stage [' . $processingStage . ']: ' . Str::limit($e->getMessage(), 100),
                ['original_error_message' => $e->getMessage(), 'stage' => $processingStage]
            );

            return null;
        }
    }

    // ... (updateSiaMessageStatus, createProcessingErrorEvent, getCsrDeviceIdentifierFromSiaMessage methods - keep your existing versions)
    protected function updateSiaMessageStatus(SiaMessageProcessingStatus $status, string $notes): void
    {
        $this->siaMessage->processing_status = $status;
        $this->siaMessage->processing_notes = ($this->siaMessage->processing_notes ? $this->siaMessage->processing_notes . "\n" : '') . $notes;
        $this->siaMessage->saveQuietly();
    }

    protected function createProcessingErrorEvent(SecurityEventType $errorType, string $description, array $details = []): void
    {
        try {
            $errorEvent = new SecurityEvent([
                'occurred_at' => CarbonImmutable::now(),
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
            $errorEvent->sourceMessage()->associate($this->siaMessage);
            // Enrichment for error events is optional but can provide context if account/device is known
            $errorEvent = EnrichSecurityEventWithRelations::run($errorEvent);
            $errorEvent->save();

            Log::info('Processing error SecurityEvent created.', ['error_event_id' => $errorEvent->id, 'original_sia_id' => $this->siaMessage->id]);
        } catch (Throwable $e) {
            Log::critical('RouteSiaMessage: CRITICAL - Failed to create processing error notification event!', [
                'original_sia_id' => $this->siaMessage->id,
                'error_creating_error_event' => $e->getMessage(),
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
