<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_health_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('backbone_name')->nullable();
            $table->unsignedInteger('up_count')->default(0);
            $table->unsignedInteger('down_count')->default(0);
            $table->unsignedInteger('unstable_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->timestamps();

            $table->unique(['date', 'server_id'], 'server_daily_stats_date_server_unique');
            $table->index(['date', 'backbone_name'], 'server_daily_stats_date_backbone_index');
            $table->index(['backbone_name', 'date'], 'server_daily_stats_backbone_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_health_daily_stats');
    }
};
