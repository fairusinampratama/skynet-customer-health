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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('ip_address');
            $table->string('status')->default('up'); // up, down, unstable
            $table->boolean('is_isolated')->default(false);
            $table->float('latency_ms')->nullable();
            $table->float('packet_loss')->nullable();
            $table->timestamp('last_alerted_at')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }

};
