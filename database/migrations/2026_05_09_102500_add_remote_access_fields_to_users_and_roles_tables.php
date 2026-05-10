<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('allow_from_remote')->default(false)->after('active');
            $table->text('allowed_ips')->nullable()->after('allow_from_remote');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('allow_from_remote')->default(false)->after('guard_name');
            $table->text('allowed_ips')->nullable()->after('allow_from_remote');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['allow_from_remote', 'allowed_ips']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['allow_from_remote', 'allowed_ips']);
        });
    }
};

