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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role', 100);
            $table->string('email')->unique();
            $table->integer('status')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('skills')->nullable();
            $table->text('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('linkedin')->nullable();
            $table->text('about')->nullable();
            $table->longText('biography')->nullable();
            $table->longText('educations')->nullable();
            $table->string('address')->nullable();
            $table->string('photo')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->longText('paymentkeys')->nullable();
            $table->string('video_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
