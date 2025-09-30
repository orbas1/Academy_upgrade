<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_access_tokens')) {
            return;
        }

        Schema::create('device_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_ip_id');
            $table->unsignedBigInteger('token_id');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('device_ip_id')->references('id')->on('device_ips')->cascadeOnDelete();
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->cascadeOnDelete();
            $table->unique(['device_ip_id', 'token_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_access_tokens');
    }
};
