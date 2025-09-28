<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable()->after('password');
                }

                if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
                }

                if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                }
            });
        }

        if (! Schema::hasTable('device_ips')) {
            Schema::create('device_ips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('ip_address')->nullable();
                $table->string('session_id')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('label')->nullable();
                $table->timestamp('trusted_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('device_ips', function (Blueprint $table) {
                if (! Schema::hasColumn('device_ips', 'ip_address')) {
                    $table->string('ip_address')->nullable()->after('user_id');
                }

                if (! Schema::hasColumn('device_ips', 'session_id')) {
                    $table->string('session_id')->nullable()->after('ip_address');
                }

                if (! Schema::hasColumn('device_ips', 'user_agent')) {
                    $table->string('user_agent')->nullable()->after('session_id');
                }

                if (! Schema::hasColumn('device_ips', 'label')) {
                    $table->string('label')->nullable()->after('user_agent');
                }

                if (! Schema::hasColumn('device_ips', 'trusted_at')) {
                    $table->timestamp('trusted_at')->nullable()->after('label');
                }

                if (! Schema::hasColumn('device_ips', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->after('trusted_at');
                }

                if (! Schema::hasColumn('device_ips', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('device_ips')) {
            Schema::table('device_ips', function (Blueprint $table) {
                foreach (['label', 'trusted_at', 'last_seen_at'] as $column) {
                    if (Schema::hasColumn('device_ips', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('device_ips', 'session_id')) {
                    $table->dropColumn('session_id');
                }

                if (Schema::hasColumn('device_ips', 'ip_address')) {
                    $table->dropColumn('ip_address');
                }

                if (Schema::hasColumn('device_ips', 'user_agent')) {
                    $table->dropColumn('user_agent');
                }
            });
        }
    }
};
