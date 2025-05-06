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
 * @property string|null $remote_ip
 * @property int|null $remote_port
 * @property string $raw_frame_hex
 * @property string|null $raw_body_hex
 * @property string|null $crc_header
 * @property string|null $length_header
 * @property string|null $protocol_token
 * @property bool $was_encrypted
 * @property string|null $sequence_number
 * @property string|null $receiver_number
 * @property string|null $line_prefix
 * @property string|null $panel_account_number
 * @property string|null $message_data
 * @property array|null $extended_data
 * @property string|null $raw_sia_timestamp
 * @property CarbonImmutable|null $sia_timestamp
 * @property ProcessingStatus $processing_status
 * @property string|null $processing_notes
 * @property string|null $response_sent_hex
 * @property CarbonImmutable|null $responded_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
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

    public function scopeWhereStatus(Builder $query, ProcessingStatus $status): Builder
    {
        return $query->where('processing_status', $status);
    }

    public function scopeParsed(Builder $query): Builder
    {
        return $query->where('processing_status', ProcessingStatus::PARSED);
    }
}
