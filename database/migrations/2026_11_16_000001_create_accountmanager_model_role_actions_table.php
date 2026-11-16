<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountmanager_model_role_actions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('model_type', 191);
            // Supports integer, UUID and ULID keys without changing Spatie's user pivots.
            $table->string('model_id', 191);
            $table->string('action', 64);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
            $table->unique(['model_type', 'model_id', 'action', 'role_id'], 'am_model_role_action_unique');
            $table->index(['model_type', 'model_id', 'action'], 'am_model_action_index');
            // Removing the last allowed role must not silently turn a restriction into inheritance.
            $table->foreign('role_id')->references('id')->on(config('permission.table_names.roles', 'roles'))->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountmanager_model_role_actions');
    }
};
