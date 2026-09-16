<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Null is deliberate: the first version never asked for a garment count.
        Schema::table('reservations', fn (Blueprint $table) => $table->unsignedSmallInteger('garment_count')->nullable());
        DB::table('reservations')->whereIn('status', ['listo', 'entregado'])->update(['status' => 'finalizada']);
        DB::table('reservations')->where('status', 'cancelado')->update(['status' => 'cancelada']);
    }

    public function down(): void
    {
        DB::table('reservations')->where('status', 'finalizada')->update(['status' => 'entregado']);
        DB::table('reservations')->where('status', 'cancelada')->update(['status' => 'cancelado']);
        Schema::table('reservations', fn (Blueprint $table) => $table->dropColumn('garment_count'));
    }
};
