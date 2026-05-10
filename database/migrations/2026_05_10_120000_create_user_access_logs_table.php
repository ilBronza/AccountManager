<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('accountmanager.logUserAccess.connection', 'activityMysql');

        Schema::connection($connection)->create('user_access_logs', function (Blueprint $table) {
            $table->id();
            // Nessun FK: users resta sul DB principale, i log su activityMysql (stesso server o DB dedicato).
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('visited_at');
            $table->string('method', 16);
            $table->text('url');
            $table->string('ip', 45);
            $table->text('user_agent')->nullable();

            $table->index(['visited_at']);
            $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        $connection = config('accountmanager.logUserAccess.connection', 'activityMysql');

        Schema::connection($connection)->dropIfExists('user_access_logs');
    }
};
