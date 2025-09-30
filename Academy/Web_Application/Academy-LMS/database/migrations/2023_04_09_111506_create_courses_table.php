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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->text('short_description')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();

            $table->string('course_type')->nullable();
            $table->string('status')->nullable();
            $table->string('level')->nullable();
            $table->string('language')->nullable();

            $table->integer('is_paid')->nullable();
            $table->boolean('is_best')->default(false);
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('discount_flag')->nullable();
            $table->decimal('discounted_price', 12, 2)->nullable();
            $table->boolean('enable_drip_content')->default(false);
            $table->longText('drip_content_settings')->nullable();

            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->string('preview')->nullable();

            $table->mediumText('description')->nullable();
            $table->mediumText('requirements')->nullable();
            $table->mediumText('outcomes')->nullable();
            $table->mediumText('faqs')->nullable();
            $table->text('instructor_ids')->nullable();
            $table->integer('average_rating')->default(0);
            $table->unsignedInteger('expiry_period')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
