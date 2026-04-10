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
            $table->string('department', 100);
            $table->string('course_name', 100);
            $table->string('major', 100)->nullable();
            $table->json('year_levels');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department', 'course_name', 'major']);
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
