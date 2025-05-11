<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use App\Services\SiaIpDc09\Enums\ResponseType;
use App\Services\SiaIpDc09\Exceptions\InvalidFrameException; // For NAK timestamp
use App\Services\SiaIpDc09\Exceptions\SiaMessageException;
use App\Services\SiaIpDc09\Exceptions\TimestampInvalidException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class HandleMessage
{
    use AsAction;

    // Assuming actions are resolved by DI or called statically if they use AsAction
    private ExtractMessageFrame $extractMessageFrame;

    private ParseMessageFrame $parseMessageFrame;

    private DetermineSiaResponse $determineSiaResponse;

    private BuildAckResponse $buildAckResponse;

    private BuildNakResponse $buildNakResponse;

    private BuildDuhResponse $buildDuhResponse;

    public function __construct(
        ExtractMessageFrame $extractMessageFrame,
        ParseMessageFrame $parseMessageFrame,
        DetermineSiaResponse $determineSiaResponse,
        BuildAckResponse $buildAckResponse,
        BuildNakResponse $buildNakResponse,
        BuildDuhResponse $buildDuhResponse
    ) {
        $this->extractMessageFrame = $extractMessageFrame;
        $this->parseMessageFrame = $parseMessageFrame;
        $this->determineSiaResponse = $determineSiaResponse;
        $this->buildAckResponse = $buildAckResponse;
        $this->buildNakResponse = $buildNakResponse;
        $this->buildDuhResponse = $buildDuhResponse;
    }

    /**
     * Handles receiving, parsing, determining response for, and building a response to a SIA message.
     *
     * @param  string  $rawMessageHex  The raw hex string received from the network.
     * @param  string  $remoteIp  Source IP.
     * @param  int  $remotePort  Source Port.
     * @return string|null The binary response string to send back, or null if no response.
     */
    public function handle(string $rawMessageHex, string $remoteIp, int $remotePort): ?string
    {
        $siaMessage = new SiaDc09Message([
            'remote_ip' => $remoteIp,
            'remote_port' => $remotePort,
            'raw_frame_hex' => $rawMessageHex, // Store original full hex
            'processing_status' => ProcessingStatus::RECEIVED,
        ]);
        $siaMessage->save(); // Save initial record

        $binaryFrame = null;
        $parsingException = null;
        $responseToSend = null;
        $responseType = ResponseType::NONE; // Default

        try {
            // 1. Extract Binary Frame
            $binaryFrame = $this->extractMessageFrame->handle($rawMessageHex, $remoteIp);

            // 2. Parse SIA Frame (this will update $siaMessage internally)
            // ParseMessageFrame will catch its own sub-action exceptions and update the model, then re-throw.
            $this->parseMessageFrame->handle($binaryFrame, $siaMessage);
            // If we reach here, $siaMessage->processing_status is PARSED

        } catch (InvalidFrameException $e) {
            $parsingException = $e; // Store exception
            // $siaMessage is already saved. Update status and notes.
            $siaMessage->processing_status = ProcessingStatus::FRAME_VALIDATION_FAILED;
            $siaMessage->processing_notes = json_encode($e->addContext(['handler' => self::class])->context());
            // No specific SIA reply for this fundamental error
            $responseType = ResponseType::NONE;
        } catch (SiaMessageException $e) {
            // This catches exceptions re-thrown by ParseMessageFrame (e.g., CrcMismatch, GenericParsing from body)
            // $siaMessage has already been updated with error status and notes by ParseMessageFrame
            $parsingException = $e; // Store exception
        } catch (Throwable $e) {
            // Unexpected error during extraction or parsing orchestration
            Log::critical('Critical error in HandleMessage before response determination.', [
                'message_id' => $siaMessage->id,
                'exception' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            $siaMessage->processing_status = ProcessingStatus::BODY_PARSING_FAILED; // Or a generic "ERROR" status
            $siaMessage->processing_notes = 'Unexpected critical error: '.$e->getMessage();
            $responseType = ResponseType::DUH; // Safest bet for unexpected server-side error
        } finally {
            if ($siaMessage->isDirty()) {
                $siaMessage->save();
            }
        }

        // 3. Determine SIA Response (using the updated $siaMessage and any caught $parsingException)
        // If $parsingException is not null, DetermineSiaResponse will use it.
        // If $parsingException is null, $siaMessage->processing_status should be PARSED.
        if (! $parsingException && $siaMessage->processing_status !== ProcessingStatus::PARSED) {
            Log::error('Logic error: No parsing exception, but message not in PARSED state for response determination.', ['message_id' => $siaMessage->id, 'status' => $siaMessage->processing_status->value]);
            // Fallback to DUH or NONE if state is inconsistent
            $responseType = $responseType === ResponseType::NONE ? ResponseType::NONE : ResponseType::DUH;
        } else {
            $responseType = $this->determineSiaResponse->handle($siaMessage, $parsingException);
        }

        // 4. Build Response String
        switch ($responseType) {
            case ResponseType::ACK:
                if ($siaMessage->processing_status === ProcessingStatus::PARSED) {
                    $responseToSend = $this->buildAckResponse->handle($siaMessage);
                } else {
                    Log::error("ACK determined but message (ID: {$siaMessage->id}) was not successfully parsed. Forcing DUH.", ['status' => $siaMessage->processing_status->value]);
                    $responseToSend = $this->buildDuhResponse->handle($siaMessage); // Fallback to DUH
                }
                break;
            case ResponseType::NAK:
                $correctiveTimestamp = CarbonImmutable::now('UTC')->format('\_H:i:s,m-d-Y'); // Default current time
                if ($parsingException instanceof TimestampInvalidException) {
                    $correctiveTimestamp = $parsingException->getCorrectiveNakTimestamp();
                }
                $responseToSend = $this->buildNakResponse->handle($correctiveTimestamp);
                break;
            case ResponseType::DUH:
                // $siaMessage would have context, or $parsingException would.
                // BuildDuhResponse needs to be flexible enough to handle either.
                $contextProvider = $parsingException ?? $siaMessage;
                $responseToSend = $this->buildDuhResponse->handle($contextProvider);
                break;
            case ResponseType::NONE:
            default:
                $responseToSend = null;
                break;
        }

        // 5. Update Model & Log Final
        if ($responseToSend !== null) {
            $siaMessage->response_sent_hex = bin2hex($responseToSend);
            $siaMessage->responded_at = CarbonImmutable::now('UTC');
            Log::info("SIA message processed. Response: {$responseType->value}", ['message_id' => $siaMessage->id, 'response_length' => strlen($responseToSend)]);
        } else {
            Log::info("SIA message processed. No response sent (Type: {$responseType->value}).", ['message_id' => $siaMessage->id]);
        }
        if ($siaMessage->isDirty()) {
            $siaMessage->save();
        }

        return $responseToSend;
    }
}
