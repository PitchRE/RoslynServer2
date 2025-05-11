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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('partition_id')->nullable()->constrained('partitions')->onDelete('set null'); // Zone can be unassigned or partition deleted

            $table->string('name'); // e.g., "Front Door", "Office Motion"
            $table->string('zone_number'); // As identified by panel (e.g., "001", "1", "1A")
            $table->string('zone_type')->nullable()->index(); // e.g., ENTRY_EXIT, PERIMETER, FIRE
            $table->string('physical_location_description')->nullable();
            $table->string('sensor_type')->nullable(); // e.g., PIR, Magnetic Contact

            $table->boolean('is_bypassed')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            // A zone number should be unique for a given device
            $table->unique(['device_id', 'zone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
