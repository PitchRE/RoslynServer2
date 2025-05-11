<?php

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
        Schema::create('partitions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade'); // Each partition is on a specific device

            $table->string('name'); // e.g., "Area 1", "Shop Floor"
            $table->string('partition_number'); // As identified by the panel (e.g., "01", "1", "A")

            $table->string('current_status')->nullable()->index(); // e.g., ARMED_AWAY, DISARMED
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            // A partition number should be unique for a given device
            $table->unique(['device_id', 'partition_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partitions');
    }
};
