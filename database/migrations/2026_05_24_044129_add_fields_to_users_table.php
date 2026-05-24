<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('is_activated')->default(false)->after('is_active');
            $table->unsignedInteger('coins_balance')->default(0)->after('is_activated');
            $table->unsignedInteger('total_points')->default(0)->after('coins_balance');
            $table->string('avatar')->nullable()->after('total_points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'is_activated', 'coins_balance', 'total_points', 'avatar']);
        });
    }
};
