<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_ips')) {
            return;
        }

        Schema::table('device_ips', function (Blueprint $table) {
            if (! Schema::hasColumn('device_ips', 'device_name')) {
                $table->string('device_name')->nullable()->after('user_agent');
            }

            if (! Schema::hasColumn('device_ips', 'platform')) {
                $table->string('platform', 100)->nullable()->after('device_name');
            }

            if (! Schema::hasColumn('device_ips', 'app_version')) {
                $table->string('app_version', 50)->nullable()->after('platform');
            }

            if (! Schema::hasColumn('device_ips', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('trusted_at');
            }

            if (! Schema::hasColumn('device_ips', 'last_headers')) {
                $table->json('last_headers')->nullable()->after('app_version');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('device_ips')) {
            return;
        }

        Schema::table('device_ips', function (Blueprint $table) {
            foreach (['device_name', 'platform', 'app_version', 'last_headers'] as $column) {
                if (Schema::hasColumn('device_ips', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('device_ips', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });
    }
};
