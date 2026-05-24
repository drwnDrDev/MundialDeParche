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
        Schema::table('prediction_submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('pts_classifier')->default(0)->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prediction_submissions', function (Blueprint $table) {
            $table->dropColumn('pts_classifier');
        });
    }
};
