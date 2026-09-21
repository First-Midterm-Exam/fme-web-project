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
        Schema::create('practice_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_area_id')->constrained('practice_areas')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 255);
            $table->unsignedTinyInteger('level');
            $table->timestamps();

            $table->index(['practice_area_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practices');
        Schema::dropIfExists('practice_areas');
    }
};
