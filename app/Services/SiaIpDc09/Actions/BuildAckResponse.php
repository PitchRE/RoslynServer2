<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Contracts\EncryptionService;
use App\Support\Crc\Contracts\CrcCalculator as CrcCalculatorContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class BuildAckResponse
{
    use AsAction;

    private const LF = "\n";

    private const CR = "\r";

    private const ACK_TOKEN_UNENCRYPTED = '"ACK"';

    private const ACK_TOKEN_ENCRYPTED = '"*ACK"';

    private const ACK_EMPTY_DATA = '[]'; // Empty data block content used inside encrypted part or directly

    private CrcCalculatorContract $crcCalculator;

    private EncryptionService $encryptionService;

    public function __construct(
        CrcCalculatorContract $crcCalculator,
        EncryptionService $encryptionService

    ) {
        $this->crcCalculator = $crcCalculator;
        $this->encryptionService = $encryptionService;
    }

    public function handle(SiaDc09Message $originalMessage): string
    {
        $logContext = [/* ... as before ... */];
        $logContext['was_original_encrypted'] = $originalMessage->was_encrypted;

        // Prepare Header Components (echoed from original)
        $ackSeq = str_pad($originalMessage->sequence_number ?? '0000', 4, '0', STR_PAD_LEFT);
        $ackRcvPart = $originalMessage->receiver_number ? 'R'.$originalMessage->receiver_number : '';
        $ackPrePart = 'L'.($originalMessage->line_prefix ?? '0');
        $ackAccPart = '#'.($originalMessage->panel_account_number ?? 'ERROR');

        // Determine response token
        $isResponseEncrypted = $originalMessage->was_encrypted;
        $ackToken = $isResponseEncrypted ? self::ACK_TOKEN_ENCRYPTED : self::ACK_TOKEN_UNENCRYPTED;

        // Prepare body content part
        $siaBodyPart = '';
        $ackTimestampString = CarbonImmutable::now('UTC')->format('\_H:i:s,m-d-Y');

        if ($isResponseEncrypted) {
            // Content to be passed to Encryption Service (handles padding internally)
            $contentToEncrypt = ']'.$ackTimestampString;

            $hexEncrypted = $this->encryptionService->handle(
                $contentToEncrypt, // Pass only the actual content
                $originalMessage->panel_account_number,
                $originalMessage->receiver_number,
                $originalMessage->line_prefix
            );

            if ($hexEncrypted === null) {
                Log::error('BuildAckResponse: Encryption service failed.', $logContext);
                throw new RuntimeException("Failed to encrypt ACK response for message ID {$originalMessage->id}");
            }

            $siaBodyPart = '['.$hexEncrypted; // Encrypted content is enclosed in brackets

        } else {
            // Unencrypted structure: []_Timestamp
            $siaBodyPart = self::ACK_EMPTY_DATA.$ackTimestampString;
        }

        // Assemble the full body for CRC/Length
        $fullBody = $ackToken.$ackSeq.$ackRcvPart.$ackPrePart.$ackAccPart.$siaBodyPart;

        // Calculate CRC and Length
        $crc = $this->crcCalculator->handle($fullBody);
        $lengthHex = str_pad(strtoupper(dechex(strlen($fullBody))), 3, '0', STR_PAD_LEFT);
        $lengthHeader = '0'.$lengthHex;

        // Assemble Final Frame
        $frame = self::LF.$crc.$lengthHeader.$fullBody.self::CR;

        $logContext['built_response_type'] = $isResponseEncrypted ? 'ACK (Encrypted)' : 'ACK (Unencrypted)';
        $logContext['response_frame_length'] = strlen($frame);
        Log::debug('Built ACK response', $logContext);

        return $frame;
    }
}
