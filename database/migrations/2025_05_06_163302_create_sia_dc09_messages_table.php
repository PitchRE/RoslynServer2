<?php

use App\Services\SiaIpDc09\Enums\ProcessingStatus; // Import your Enum
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sia_dc09_messages', function (Blueprint $table) {
            $table->id(); // Standard auto-incrementing primary key

            // Raw Message & Context
            $table->ipAddress('remote_ip')->nullable()->comment('Source IP address of the message.');
            $table->unsignedInteger('remote_port')->nullable()->comment('Source port of the message.');
            $table->text('raw_frame_hex')->comment('The complete raw message frame as received (hex-encoded). Storing hex for easier raw data inspection.');
            $table->text('raw_body_hex')->nullable()->comment('The extracted binary message body (hex-encoded), if frame validation passed.');

            // SIA Frame Header Components (Parsed)
            $table->string('crc_header', 4)->nullable()->comment('The 4-char hex CRC from the frame header.');
            $table->string('length_header', 4)->nullable()->comment("The 4-char '0LLL' length string from the header."); // Stores "0LLL"

            // SIA Body Header Components (Parsed)
            $table->string('protocol_token', 50)->nullable()->index()->comment('The SIA protocol ID token (e.g., ADM-CID, SIA-DCS), without encryption marker.');
            $table->boolean('was_encrypted')->default(false)->comment('Indicates if the original message was encrypted (had * prefix).');
            $table->string('sequence_number', 4)->nullable()->comment('The 4-digit sequence number from the message.');
            $table->string('receiver_number', 6)->nullable()->comment('The optional receiver number (R... part).');
            $table->string('line_prefix', 6)->nullable()->comment('The line/account prefix (L... part).');
            $table->string('panel_account_number', 16)->nullable()->index()->comment('The panel account number (#... part).');

            // SIA Body Content (Parsed)
            $table->text('message_data')->nullable()->comment('The main data content from within the [...] block (decrypted if applicable).');
            $table->json('extended_data')->nullable()->comment('Optional extended data fields as a JSON object.');
            $table->string('raw_sia_timestamp', 20)->nullable()->comment('The raw timestamp string (_HH:MM:SS,MM-DD-YYYY) from the message.');
            $table->timestampTz('sia_timestamp', 0)->nullable()->comment('The parsed SIA timestamp (stored in UTC). Precision 0 for seconds.'); // Stored as proper timestamp

            // Processing Information
            $table->string('processing_status', 50)->default(ProcessingStatus::RECEIVED->value)->index()->comment('Current processing status of the message.');
            $table->text('processing_notes')->nullable()->comment('Notes or error messages related to processing.');
            $table->text('response_sent_hex')->nullable()->comment('The SIA response sent back to the panel (hex-encoded).');
            $table->timestampTz('responded_at', 0)->nullable()->comment('Timestamp when the response was sent.');

            // Standard Laravel Timestamps
            $table->timestamps(0); // created_at and updated_at with timezone, precision 0

            // Indexes for common query patterns
            $table->index(['panel_account_number', 'sequence_number']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sia_dc09_messages');
    }
};
