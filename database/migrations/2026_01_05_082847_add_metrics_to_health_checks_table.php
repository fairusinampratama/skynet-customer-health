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
        Schema::table('health_checks', function (Blueprint $table) {
            $table->integer('latency_ms')->nullable()->after('status');
            $table->float('packet_loss')->default(0)->after('latency_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_checks', function (Blueprint $table) {
            $table->dropColumn(['latency_ms', 'packet_loss']);
        });
    }
};
