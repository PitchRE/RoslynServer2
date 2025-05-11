<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id(); // Auto-incrementing BIGINT primary key

            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade'); // Each device must belong to a site
            // onDelete('cascade') means if a site is deleted, its devices are also deleted.
            // Choose 'set null' if a device can exist without a site (less common for panels).

            $table->string('name')->nullable(); // A human-friendly name for the device
            $table->string('identifier')->unique()->nullable(); // Unique identifier like MAC, Serial, or Receiver Line# + Account#
            // Making it nullable initially if it's not always immediately available, but should be unique if present.

            $table->string('model_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('firmware_version')->nullable();

            // For IP communication
            $table->string('ip_address')->nullable();
            $table->integer('port')->unsigned()->nullable();

            $table->string('communication_protocol')->nullable()->index(); // e.g., CONTACT_ID, SIA_DC09

            $table->boolean('is_active')->default(true)->index(); // Is the device actively monitored?

            $table->date('installation_date')->nullable();
            $table->timestamp('last_communication_at')->nullable()->index(); // Track device health

            $table->text('notes')->nullable();
            $table->json('configuration_details')->nullable(); // Store panel-specific configurations

            // $table->softDeletes(); // Uncomment if you use SoftDeletes trait in the model
            $table->timestamps(); // created_at and updated_at

            // Add unique constraint for site_id and identifier if an identifier must be unique *within a site*
            // $table->unique(['site_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};