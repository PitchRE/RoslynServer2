<?php

declare(strict_types=1);

namespace App\Services\SiaIpDc09\Actions;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Exceptions\SiaMessageException;
use App\Support\Crc\Contracts\CrcCalculator as CrcCalculatorContract;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction; // To extract context on failure

class BuildDuhResponse
{
    use AsAction;

    private const LF = "\n"; // 0x0A

    private const CR = "\r"; // 0x0D

    private const DUH_TOKEN = '"DUH"';

    private const DUH_DATA_BRACKETS = '[]';

    private CrcCalculatorContract $crcCalculator;

    public function __construct(CrcCalculatorContract $crcCalculator)
    {
        $this->crcCalculator = $crcCalculator;
    }

    /**
     * Builds a SIA DC-09 DUH (Unable to Handle) response string.
     * DUHs are never encrypted and echo original identifiers if available.
     *
     * @param  SiaDc09Message|SiaMessageException  $contextProvider  Provides context.
     *                                                               - If SiaDc09Message: Assumed to be the model instance related to the DUH condition.
     *                                                               - If SiaMessageException: Used to extract header info if parsing failed early.
     * @return string The full binary DUH response frame string.
     */
    public function handle(SiaDc09Message|SiaMessageException $contextProvider): string
    {
        // Extract necessary identifiers safely from the context provider
        $sequence = '0000';
        $receiverPart = '';
        $prefixPart = 'L0';
        $accountPart = '#ERROR';
        $logContext = [];

        if ($contextProvider instanceof SiaDc09Message) {
            $model = $contextProvider;
            $logContext['source'] = 'SiaDc09Message';
            $logContext['message_id'] = $model->id;
            $sequence = str_pad($model->sequence_number ?? '0000', 4, '0', STR_PAD_LEFT);
            $receiverPart = $model->receiver_number ? 'R'.$model->receiver_number : '';
            $prefixPart = 'L'.($model->line_prefix ?? '0');
            $accountPart = '#'.($model->panel_account_number ?? 'ERROR');
        } else {
            // If not a SiaDc09Message, it must be a SiaMessageException due to type hint
            $exception = $contextProvider;
            $logContext['source'] = 'SiaMessageException';
            $logContext['exception_class'] = get_class($exception);
            $header = $exception->getParsedHeaderParts(); // Get potentially partially parsed header
            if ($header) {
                $sequence = str_pad($header['sequence_number'] ?? '0000', 4, '0', STR_PAD_LEFT);
                $receiverPart = isset($header['receiver_number']) ? 'R'.$header['receiver_number'] : '';
                $prefixPart = isset($header['line_prefix']) ? 'L'.$header['line_prefix'] : 'L0';
                $accountPart = isset($header['panel_account_number']) ? '#'.$header['panel_account_number'] : '#ERROR';
            } else {
                Log::warning('Building DUH from exception, but parsedHeader context was missing in exception.', $exception->context());
            }
        }

        // Construct the body part for CRC/Length calculation
        $body = self::DUH_TOKEN
            .$sequence
            .$receiverPart // Append only if present
            .$prefixPart
            .$accountPart
            .self::DUH_DATA_BRACKETS;

        // Calculate CRC
        $crc = $this->crcCalculator->handle($body);
        $crcHeaderLength = $this->crcCalculator->getHexStringLength();

        // Calculate Length Header
        $bodyLength = strlen($body);
        $lengthHex = str_pad(strtoupper(dechex($bodyLength)), 3, '0', STR_PAD_LEFT);
        $lengthHeader = '0'.$lengthHex;

        // Assemble the final frame
        $frame = self::LF.$crc.$lengthHeader.$body.self::CR;

        $logContext['built_response_type'] = 'DUH';
        $logContext['sequence_echoed'] = $sequence;
        $logContext['account_echoed'] = $accountPart;
        $logContext['response_frame_length'] = strlen($frame);
        Log::debug('Built DUH response', $logContext);

        return $frame;
    }
}
