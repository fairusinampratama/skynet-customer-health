<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasIndex('customers', 'customers_area_monitoring_index')) {
                $table->index(['area_id', 'is_isolated', 'status', 'updated_at'], 'customers_area_monitoring_index');
            }
        });

        if (Schema::hasTable('health_check_daily_stats')) {
            Schema::table('health_check_daily_stats', function (Blueprint $table) {
                if (! Schema::hasIndex('health_check_daily_stats', 'daily_stats_date_area_index')) {
                    $table->index(['date', 'area_id'], 'daily_stats_date_area_index');
                }
            });
        }

        Schema::table('server_health_checks', function (Blueprint $table) {
            if (! Schema::hasIndex('server_health_checks', 'server_checks_status_checked_server_index')) {
                $table->index(['status', 'checked_at', 'server_id'], 'server_checks_status_checked_server_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasIndex('customers', 'customers_area_monitoring_index')) {
                $table->dropIndex('customers_area_monitoring_index');
            }
        });

        if (Schema::hasTable('health_check_daily_stats')) {
            Schema::table('health_check_daily_stats', function (Blueprint $table) {
                if (Schema::hasIndex('health_check_daily_stats', 'daily_stats_date_area_index')) {
                    $table->dropIndex('daily_stats_date_area_index');
                }
            });
        }

        Schema::table('server_health_checks', function (Blueprint $table) {
            if (Schema::hasIndex('server_health_checks', 'server_checks_status_checked_server_index')) {
                $table->dropIndex('server_checks_status_checked_server_index');
            }
        });
    }
};
