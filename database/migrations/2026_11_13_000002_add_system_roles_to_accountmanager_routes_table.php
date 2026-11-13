<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accountmanager_routes', function (Blueprint $table) {
            $table->json('system_roles')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accountmanager_routes', function (Blueprint $table) {
            $table->dropColumn('system_roles');
        });
    }
};
