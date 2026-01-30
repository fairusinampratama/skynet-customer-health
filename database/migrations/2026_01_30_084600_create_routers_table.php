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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->string('username');
            $table->text('password'); // Will be encrypted
            $table->integer('port')->default(8728);
            $table->boolean('is_active')->default(true);
            
            // Health Metrics
            $table->string('status')->default('unknown'); // up, down, unstable
            $table->integer('cpu_load')->nullable();
            $table->bigInteger('free_memory')->nullable();
            $table->bigInteger('total_memory')->nullable();
            $table->float('disk_usage')->nullable();
            
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_alerted_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
