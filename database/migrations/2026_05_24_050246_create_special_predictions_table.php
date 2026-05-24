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
        Schema::create('special_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('champion_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('runner_up_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('top_scorer_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->unsignedTinyInteger('pts_champion')->default(0);
            $table->unsignedTinyInteger('pts_runner_up')->default(0);
            $table->unsignedTinyInteger('pts_top_scorer')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_predictions');
    }
};
