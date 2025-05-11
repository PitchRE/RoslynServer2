<?php

use App\Enums\SecurityEventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; // Import your status enum for default value

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id(); // Auto-incrementing BIGINT primary key

            // Timestamps for event lifecycle
            $table->timestamp('occurred_at')->nullable()->index(); // When the event happened at the source
            $table->timestamp('processed_at')->nullable();      // When this normalized event record was created/processed

            $table->string('external_event_id')->nullable()->index(); // For correlation with other systems

            // Source Information
            $table->string('source_protocol')->nullable()->index(); // e.g., CONTACT_ID, SIA_DC09, SYSTEM_GENERATED
            $table->string('raw_event_code')->nullable();
            $table->text('raw_event_description')->nullable();

            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null'); // Or your devices table name
            $table->string('raw_device_identifier')->nullable();

            // Location Specifics
            $table->foreignId('partition_id')->nullable()->constrained('partitions')->onDelete('set null'); // Or your partitions table
            $table->string('raw_partition_identifier')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null'); // Or your zones table
            $table->string('raw_zone_identifier')->nullable();

            // Normalized Categorization (using string columns for enums)
            $table->string('event_category')->nullable()->index(); // Cast to SecurityEventCategory enum
            $table->string('event_type')->nullable()->index();     // Cast to SecurityEventType enum
            $table->string('event_qualifier')->nullable()->index(); // Cast to SecurityEventQualifier enum

            $table->tinyInteger('priority')->nullable()->index(); // e.g., 1 (Low) to 5 (Critical)

            // Descriptions & Details
            $table->text('normalized_description')->nullable();
            $table->json('message_details')->nullable(); // For storing extra parsed data or context as JSON

            // Workflow Status
            $table->string('status')->default(SecurityEventStatus::NEW->value)->index(); // Cast to SecurityEventStatus enum

            // Operator Actions
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by_operator_id')->nullable()->constrained('users')->onDelete('set null'); // Assuming operators are in 'users' table

            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_operator_id')->nullable()->constrained('users')->onDelete('set null'); // Assuming operators are in 'users' table
            $table->foreignId('resolution_code_id')->nullable()->constrained('resolution_codes')->onDelete('set null');
            $table->text('resolution_notes')->nullable();

            // For MorphTo relationship (if linking to specific raw log tables)
            $table->nullableMorphs('source_message'); // Adds source_message_id (UNSIGNED BIGINT) and source_message_type (STRING)

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
