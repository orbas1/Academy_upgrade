<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_scans', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('absolute_path');
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->text('details')->nullable();
            $table->string('quarantine_path')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_scans');
    }
};
