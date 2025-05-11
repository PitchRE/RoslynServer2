<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Use CarbonImmutable for date casting
use Illuminate\Database\Eloquent\Model; // Import your Enum

/**
 * App\Models\SiaDc09Message
 *
 * Represents a received SIA DC-09 message and its processing state.
 *
 * @property int $id
 * @property string|null $remote_ip Source IP address of the message.
 * @property int|null $remote_port Source port of the message.
 * @property string $raw_frame_hex The complete raw message frame as received (hex-encoded). Storing hex for easier raw data inspection.
 * @property string|null $raw_body_hex The extracted binary message body (hex-encoded), if frame validation passed.
 * @property string|null $crc_header The 4-char hex CRC from the frame header.
 * @property string|null $length_header The 4-char '0LLL' length string from the header.
 * @property string|null $protocol_token The SIA protocol ID token (e.g., ADM-CID, SIA-DCS), without encryption marker.
 * @property bool $was_encrypted Indicates if the original message was encrypted (had * prefix).
 * @property string|null $sequence_number The 4-digit sequence number from the message.
 * @property string|null $receiver_number The optional receiver number (R... part).
 * @property string|null $line_prefix The line/account prefix (L... part).
 * @property string|null $panel_account_number The panel account number (#... part).
 * @property string|null $message_data The main data content from within the [...] block (decrypted if applicable).
 * @property array<array-key, mixed>|null $extended_data Optional extended data fields as a JSON object.
 * @property string|null $raw_sia_timestamp The raw timestamp string (_HH:MM:SS,MM-DD-YYYY) from the message.
 * @property CarbonImmutable|null $sia_timestamp The parsed SIA timestamp (stored in UTC). Precision 0 for seconds.
 * @property ProcessingStatus $processing_status Current processing status of the message.
 * @property string|null $processing_notes Notes or error messages related to processing.
 * @property string|null $response_sent_hex The SIA response sent back to the panel (hex-encoded).
 * @property CarbonImmutable|null $responded_at Timestamp when the response was sent.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $raw_body_binary
 * @property-read string|null $raw_frame_binary
 * @property-read string|null $response_sent_binary
 *
 * @method static \Database\Factories\SiaDc09MessageFactory factory($count = null, $state = [])
 * @method static Builder<static>|SiaDc09Message newModelQuery()
 * @method static Builder<static>|SiaDc09Message newQuery()
 * @method static Builder<static>|SiaDc09Message parsed()
 * @method static Builder<static>|SiaDc09Message query()
 * @method static Builder<static>|SiaDc09Message whereCrcHeader($value)
 * @method static Builder<static>|SiaDc09Message whereCreatedAt($value)
 * @method static Builder<static>|SiaDc09Message whereExtendedData($value)
 * @method static Builder<static>|SiaDc09Message whereId($value)
 * @method static Builder<static>|SiaDc09Message whereLengthHeader($value)
 * @method static Builder<static>|SiaDc09Message whereLinePrefix($value)
 * @method static Builder<static>|SiaDc09Message whereMessageData($value)
 * @method static Builder<static>|SiaDc09Message wherePanelAccountNumber($value)
 * @method static Builder<static>|SiaDc09Message whereProcessingNotes($value)
 * @method static Builder<static>|SiaDc09Message whereProcessingStatus($value)
 * @method static Builder<static>|SiaDc09Message whereProtocolToken($value)
 * @method static Builder<static>|SiaDc09Message whereRawBodyHex($value)
 * @method static Builder<static>|SiaDc09Message whereRawFrameHex($value)
 * @method static Builder<static>|SiaDc09Message whereRawSiaTimestamp($value)
 * @method static Builder<static>|SiaDc09Message whereReceiverNumber($value)
 * @method static Builder<static>|SiaDc09Message whereRemoteIp($value)
 * @method static Builder<static>|SiaDc09Message whereRemotePort($value)
 * @method static Builder<static>|SiaDc09Message whereRespondedAt($value)
 * @method static Builder<static>|SiaDc09Message whereResponseSentHex($value)
 * @method static Builder<static>|SiaDc09Message whereSequenceNumber($value)
 * @method static Builder<static>|SiaDc09Message whereSiaTimestamp($value)
 * @method static Builder<static>|SiaDc09Message whereStatus(\App\Services\SiaIpDc09\Enums\ProcessingStatus $status)
 * @method static Builder<static>|SiaDc09Message whereUpdatedAt($value)
 * @method static Builder<static>|SiaDc09Message whereWasEncrypted($value)
 *
 * @mixin \Eloquent
 */
class SiaDc09Message extends Model
{
    /** @use HasFactory<\Database\Factories\SiaDc09MessageFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sia_dc09_messages';

    /**
     * The attributes that are mass assignable.
     *
     * Using `guarded = []` to make all attributes mass assignable is common for DTO-driven creation.
     * Alternatively, list all fields in `$fillable`. Be cautious with `guarded = []` in general applications.
     * For this specific use case where creation is controlled via DTOs, it's often acceptable.
     *
     * @var list<string>
     */
    // protected $guarded = []; // Allows all attributes to be mass-assigned.
    // OR define $fillable explicitly:

    protected $fillable = [
        'remote_ip',
        'remote_port',
        'raw_frame_hex',
        'raw_body_hex',
        'crc_header',
        'length_header',
        'protocol_token',
        'was_encrypted',
        'sequence_number',
        'receiver_number',
        'line_prefix',
        'panel_account_number',
        'message_data',
        'extended_data',
        'raw_sia_timestamp',
        'sia_timestamp',
        'processing_status',
        'processing_notes',
        'response_sent_hex',
        'responded_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'remote_port' => 'integer',
        'was_encrypted' => 'boolean',
        'extended_data' => 'array', // Casts JSON column to PHP array and vice-versa
        'sia_timestamp' => 'immutable_datetime:Y-m-d H:i:s', // Cast to CarbonImmutable, assumes UTC storage
        'responded_at' => 'immutable_datetime:Y-m-d H:i:s',  // Cast to CarbonImmutable
        'processing_status' => ProcessingStatus::class, // Cast to your ProcessingStatus Enum
    ];

    /**
     * The "booted" method of the model.
     * Can be used to set default values, e.g., for processing_status.
     */
    protected static function booted(): void
    {
        static::creating(function (SiaDc09Message $message) {
            if (empty($message->processing_status)) {
                $message->processing_status = ProcessingStatus::RECEIVED;
            }
        });
    }

    // --- Potential Accessors & Mutators or Helper Methods ---

    /**
     * Helper to get the binary representation of the raw frame.
     * Useful if you decide to store raw_frame_hex but often need binary.
     */
    public function getRawFrameBinaryAttribute(): ?string
    {
        if ($this->raw_frame_hex) {
            $binary = hex2bin($this->raw_frame_hex);

            return $binary === false ? null : $binary;
        }

        return null;
    }

    /**
     * Helper to get the binary representation of the raw body.
     */
    public function getRawBodyBinaryAttribute(): ?string
    {
        if ($this->raw_body_hex) {
            $binary = hex2bin($this->raw_body_hex);

            return $binary === false ? null : $binary;
        }

        return null;
    }

    /**
     * Helper to get the binary representation of the response sent.
     */
    public function getResponseSentBinaryAttribute(): ?string
    {
        if ($this->response_sent_hex) {
            $binary = hex2bin($this->response_sent_hex);

            return $binary === false ? null : $binary;
        }

        return null;
    }

    /**
     * Define the NAK-related processing statuses.
     * These are the statuses that indicate this SiaDc09Message record itself
     * resulted in a NAK being sent back to the panel.
     *
     * @return array<int, ProcessingStatus>
     */
    public static function getNakSentStatuses(): array
    {
        return [
            ProcessingStatus::NAK_SENT_TIMESTAMP_REJECT,
        ];
    }

    /**
     * Determine if this specific SiaDc09Message record represents an instance
     * where a NAK was sent in response to the incoming signal.
     *
     * This method checks the current processing_status of THIS message.
     * It does NOT tell you if a subsequent successful message was a retransmission of this one.
     */
    public function wasNakd(): bool
    {
        return in_array($this->processing_status, self::getNakSentStatuses());
    }

    public function scopeWhereStatus(Builder $query, ProcessingStatus $status): Builder
    {
        return $query->where('processing_status', $status);
    }

    public function scopeParsed(Builder $query): Builder
    {
        return $query->where('processing_status', ProcessingStatus::PARSED);
    }
}
