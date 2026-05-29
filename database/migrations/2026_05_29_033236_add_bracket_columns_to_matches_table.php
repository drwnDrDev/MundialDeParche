<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('winner_feeds_match_id')->nullable()->after('winner_team_id');
            $table->enum('winner_feeds_slot', ['home', 'away'])->nullable()->after('winner_feeds_match_id');

            $table->foreign('winner_feeds_match_id')
                  ->references('id')->on('matches')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['winner_feeds_match_id']);
            $table->dropColumn(['winner_feeds_match_id', 'winner_feeds_slot']);
        });
    }
};
