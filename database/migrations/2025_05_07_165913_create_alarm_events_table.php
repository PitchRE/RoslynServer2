<?php

// database/migrations/YYYY_MM_DD_HHMMSS_create_alarm_events_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_events', function (Blueprint $table) {
            $table->id();

            $table->timestampsTz(0); // created_at, updated_at

            // Optional foreign key to a 'sites' or 'panels' table based on panel_account_number if you have one
            // $table->foreign('panel_account_number')->references('account')->on('sites');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_events');
    }
};
