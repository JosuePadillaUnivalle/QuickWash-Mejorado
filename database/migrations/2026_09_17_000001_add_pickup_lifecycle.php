<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_ends_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->index(['machine_id', 'status', 'starts_at'], 'reservations_lifecycle_index');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_lifecycle_index');
            $table->dropColumn(['processing_started_at', 'processing_ends_at', 'collected_at']);
        });
    }
};
