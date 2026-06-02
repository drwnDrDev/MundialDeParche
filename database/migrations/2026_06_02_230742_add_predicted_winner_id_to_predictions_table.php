<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->foreignId('predicted_winner_id')
                  ->nullable()
                  ->after('predicted_away')
                  ->constrained('teams')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Team::class, 'predicted_winner_id');
            $table->dropColumn('predicted_winner_id');
        });
    }
};
