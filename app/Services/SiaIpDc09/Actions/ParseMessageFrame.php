<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Data\ParsedHeaderDto;
// We don't directly return FinalParsedSiaDataDto from this action anymore,
// but it conceptually represents the data we gather to populate the model.
// use App\Services\SiaIpDc09\Data\FinalParsedSiaDataDto;
use App\Services\SiaIpDc09\Enums\ErrorContext;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use App\Services\SiaIpDc09\Exceptions\GenericParsingException;
use App\Services\SiaIpDc09\Exceptions\SiaMessageException;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class ParseMessageFrame
{
    use AsAction;

    private ValidateSiaFrame $validateSiaFrame;

    private ParseSiaBodyHeader $parseSiaBodyHeader;

    private ProcessSiaDataContent $processSiaDataContent;

    public function __construct(
        ValidateSiaFrame $validateSiaFrame,
        ParseSiaBodyHeader $parseSiaBodyHeader,
        ProcessSiaDataContent $processSiaDataContent
    ) {
        $this->validateSiaFrame = $validateSiaFrame;
        $this->parseSiaBodyHeader = $parseSiaBodyHeader;
        $this->processSiaDataContent = $processSiaDataContent;
    }

    /**
     * Orchestrates the parsing of a raw binary SIA DC-09 message frame.
     * Updates the provided SiaDc09Message model with parsed data or error information.
     *
     * @param  string  $binaryFrame  The raw binary message frame (CRC+Length+Body), output from ExtractMessageFrame.
     * @param  SiaDc09Message  $siaMessage  The Eloquent model instance to update.
     *                                      It's assumed this model already has initial context like remote_ip, remote_port, raw_frame_hex.
     * @return SiaDc09Message The updated Eloquent model.
     *
     * @throws SiaMessageException On any parsing or validation failure from sub-actions.
     * @throws Throwable For any other unexpected errors during orchestration.
     */
    public function handle(string $binaryFrame, SiaDc09Message $siaMessage): SiaDc09Message
    {
        $logContext = [
            'message_id' => $siaMessage->id,
            'remote_ip' => $siaMessage->remote_ip,
            'panel_account_number' => null, // Will be populated
            'current_stage' => 'initialization', // Initial stage
        ];

        $validatedFrameDto = null;
        $parsedHeaderDto = null;
        // $parsedContentDto is used locally within the try block

        try {
            $logContext['current_stage'] = 'frame_validation';
            $validatedFrameDto = $this->validateSiaFrame->handle($binaryFrame);
            // Populate model from ValidatedFrameDto
            $siaMessage->raw_body_hex = bin2hex($validatedFrameDto->rawBody);
            $siaMessage->crc_header = $validatedFrameDto->crcHeader;
            $siaMessage->length_header = '0'.str_pad(strtoupper(dechex($validatedFrameDto->lengthHeaderValue)), 3, '0', STR_PAD_LEFT);

            $logContext['current_stage'] = 'body_header_parsing';
            $parsedHeaderDto = $this->parseSiaBodyHeader->handle($validatedFrameDto);
            // Update log context with key info
            $logContext['panel_account_number'] = $parsedHeaderDto->panelAccountNumber;
            $logContext['protocol_token'] = $parsedHeaderDto->protocolToken;
            $logContext['sequence_number'] = $parsedHeaderDto->sequenceNumber;
            // Populate model from ParsedHeaderDto
            $this->populateBaseHeaderData($siaMessage, $parsedHeaderDto);

            $logContext['current_stage'] = 'data_content_processing';
            $parsedContentDto = $this->processSiaDataContent->handle($parsedHeaderDto);
            // Populate model from ParsedContentDto
            $siaMessage->message_data = $parsedContentDto->messageData;
            $siaMessage->extended_data = ! empty($parsedContentDto->extendedData) ? $parsedContentDto->extendedData : null;
            $siaMessage->raw_sia_timestamp = $parsedContentDto->rawSiaTimestamp;
            $siaMessage->sia_timestamp = $parsedContentDto->siaTimestamp;

            $logContext['current_stage'] = 'parsing_successful';
            $siaMessage->processing_status = ProcessingStatus::PARSED;
            $siaMessage->processing_notes = 'Successfully parsed SIA frame.';
            Log::info('SIA Frame parsing orchestration completed successfully.', $logContext);

        } catch (SiaMessageException $e) {
            // $logContext will have 'current_stage' reflecting where in the 'try' block it failed
            $exceptionContext = $e->addContext($logContext)->context();
            Log::warning('SIA Message Parsing Pipeline Failed.', $exceptionContext);

            $siaMessage->processing_notes = json_encode($exceptionContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($e->getErrorContextType() === ErrorContext::FRAME_VALIDATION) {
                $siaMessage->processing_status = ProcessingStatus::FRAME_VALIDATION_FAILED;
            } else { // BODY_PARSING or UNKNOWN from SiaMessageException
                $siaMessage->processing_status = ProcessingStatus::BODY_PARSING_FAILED;
            }
            // Populate any header parts that were successfully parsed before the exception
            if ($e->getParsedHeaderParts()) {
                $this->populatePartialHeaderData($siaMessage, $e->getParsedHeaderParts());
            } elseif ($parsedHeaderDto instanceof ParsedHeaderDto) {
                // If exception happened after full header parsing (e.g., in ProcessSiaDataContent)
                // ensure base header data is on the model.
                $this->populateBaseHeaderData($siaMessage, $parsedHeaderDto);
            }

            throw $e; // Re-throw the specific exception for HandleMessage to catch
        } catch (Throwable $e) {
            // $logContext['current_stage'] reflects where it was before this unexpected error
            Log::critical('Unexpected error during SIA frame parsing orchestration.', $logContext + [
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 500),
            ]);

            $siaMessage->processing_status = ProcessingStatus::BODY_PARSING_FAILED; // Or a more generic system_error status
            $siaMessage->processing_notes = 'Unexpected orchestration error at stage ['.$logContext['current_stage'].']: '.$e->getMessage();

            if ($parsedHeaderDto instanceof ParsedHeaderDto) {
                // If header parsing completed, all its data should be on the model
                $this->populateBaseHeaderData($siaMessage, $parsedHeaderDto);
            }

            throw new GenericParsingException(
                message: 'Unexpected orchestration error: '.$e->getMessage(),
                fullRawFrame: $binaryFrame,
                /** @phpstan-ignore nullsafe.neverNull */
                extractedMessageBody: $validatedFrameDto?->rawBody, // Nullsafe is correct
                errorContext: ErrorContext::UNKNOWN,
                offsetWithinContext: null,
                parsedHeaderParts: $parsedHeaderDto ? (array) $parsedHeaderDto : null, // Provide if available
                previous: $e
            );
        } finally {
            // Always save the message model to persist status and parsed data/errors
            if ($siaMessage->isDirty()) {
                $siaMessage->saveQuietly();
            }
        }

        return $siaMessage;
    }

    /**
     * Helper to populate model fields from the fully parsed header DTO.
     */
    private function populateBaseHeaderData(SiaDc09Message $siaMessage, ParsedHeaderDto $dto): void
    {
        $siaMessage->protocol_token = $dto->protocolToken;
        $siaMessage->was_encrypted = $dto->wasEncrypted;
        $siaMessage->sequence_number = $dto->sequenceNumber;
        $siaMessage->receiver_number = $dto->receiverNumber;
        $siaMessage->line_prefix = $dto->linePrefix;
        $siaMessage->panel_account_number = $dto->panelAccountNumber;
    }

    /**
     * Helper to populate model fields from partially parsed header data
     * (typically an array from an exception's context).
     */
    private function populatePartialHeaderData(SiaDc09Message $siaMessage, array $parsedHeaderParts): void
    {
        // Only set if the model's property is currently null (or not set to its final type yet)
        // AND the key exists in the partial data with the correct type.
        if (is_null($siaMessage->protocol_token) && array_key_exists('protocol_token', $parsedHeaderParts) && is_string($parsedHeaderParts['protocol_token'])) {
            $siaMessage->protocol_token = $parsedHeaderParts['protocol_token'];
        }
        // For was_encrypted, it's a boolean and defaults to false.
        // Only update if explicitly provided in partial data and model's is still default.
        if ($siaMessage->was_encrypted === false && array_key_exists('was_encrypted', $parsedHeaderParts) && is_bool($parsedHeaderParts['was_encrypted'])) {
            $siaMessage->was_encrypted = $parsedHeaderParts['was_encrypted'];
        }
        if (is_null($siaMessage->sequence_number) && array_key_exists('sequence_number', $parsedHeaderParts) && is_string($parsedHeaderParts['sequence_number'])) {
            $siaMessage->sequence_number = $parsedHeaderParts['sequence_number'];
        }
        if (is_null($siaMessage->receiver_number) && array_key_exists('receiver_number', $parsedHeaderParts)) {
            if (is_string($parsedHeaderParts['receiver_number']) || is_null($parsedHeaderParts['receiver_number'])) {
                $siaMessage->receiver_number = $parsedHeaderParts['receiver_number'];
            } else {
                Log::warning('Unexpected type for partial receiver_number during error population', ['type' => gettype($parsedHeaderParts['receiver_number'])]);
            }
        }
        if (is_null($siaMessage->line_prefix) && array_key_exists('line_prefix', $parsedHeaderParts) && is_string($parsedHeaderParts['line_prefix'])) {
            $siaMessage->line_prefix = $parsedHeaderParts['line_prefix'];
        }
        if (is_null($siaMessage->panel_account_number) && array_key_exists('panel_account_number', $parsedHeaderParts) && is_string($parsedHeaderParts['panel_account_number'])) {
            $siaMessage->panel_account_number = $parsedHeaderParts['panel_account_number'];
        }
    }
}
