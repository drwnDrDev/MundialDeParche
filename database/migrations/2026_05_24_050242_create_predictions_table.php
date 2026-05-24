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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('predicted_home');
            $table->unsignedTinyInteger('predicted_away');
            $table->unsignedTinyInteger('pts_exact')->default(0);
            $table->unsignedTinyInteger('pts_result')->default(0);
            $table->unsignedTinyInteger('pts_classifier')->default(0);
            $table->unsignedTinyInteger('total_points')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
