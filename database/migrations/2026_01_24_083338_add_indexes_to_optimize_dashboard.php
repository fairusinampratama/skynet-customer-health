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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasIndex('customers', 'customers_status_is_isolated_index')) {
                $table->index(['status', 'is_isolated'], 'customers_status_is_isolated_index');
            }
        });

        Schema::table('health_checks', function (Blueprint $table) {
            if (!Schema::hasIndex('health_checks', 'health_checks_customer_id_checked_at_index')) {
                $table->index(['customer_id', 'checked_at'], 'health_checks_customer_id_checked_at_index');
            }
            if (!Schema::hasIndex('health_checks', 'health_checks_status_checked_at_index')) {
                $table->index(['status', 'checked_at'], 'health_checks_status_checked_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_status_is_isolated_index');
        });

        Schema::table('health_checks', function (Blueprint $table) {
            $table->dropIndex('health_checks_customer_id_checked_at_index');
            $table->dropIndex('health_checks_status_checked_at_index');
        });
    }
};
