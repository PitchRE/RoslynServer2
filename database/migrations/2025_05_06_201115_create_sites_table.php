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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            // $table->foreignId('dealer_id')->nullable()->constrained('dealers')->onDelete('set null');

            $table->string('name');

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            $table->decimal('latitude', 10, 8)->nullable(); // For mapping
            $table->decimal('longitude', 11, 8)->nullable(); // For mapping

            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_phone')->nullable();
            $table->string('primary_contact_email')->nullable();

            $table->string('timezone')->nullable()->default('UTC');

            $table->boolean('is_active')->default(true)->index();
            $table->string('monitoring_service_level')->nullable();

            $table->text('notes')->nullable();

            // $table->softDeletes(); // Uncomment for soft deletes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
