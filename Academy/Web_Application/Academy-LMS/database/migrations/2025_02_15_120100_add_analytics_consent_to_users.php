<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestampTz('analytics_consent_at')->nullable()->after('email_verified_at');
            $table->string('analytics_consent_version')->nullable()->after('analytics_consent_at');
            $table->timestampTz('analytics_consent_revoked_at')->nullable()->after('analytics_consent_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'analytics_consent_at',
                'analytics_consent_version',
                'analytics_consent_revoked_at',
            ]);
        });
    }
};
