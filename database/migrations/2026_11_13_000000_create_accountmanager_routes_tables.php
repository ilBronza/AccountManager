<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountmanager_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->unique();
            $table->char('signature', 64)->unique();
            $table->text('domain')->nullable();
            $table->text('uri');
            $table->json('methods');
            $table->text('action')->nullable();
            $table->boolean('is_registered')->default(true)->index();
            $table->timestamps();
        });

        foreach (['role', 'permission'] as $type) {
            $model = app(config('permission.models.'.$type));
            Schema::create('accountmanager_route_'.$type, function (Blueprint $table) use ($type, $model) {
                $table->foreignId('route_id')->constrained('accountmanager_routes')->cascadeOnDelete();
                $table->unsignedBigInteger($type.'_id');
                $table->foreign($type.'_id')->references($model->getKeyName())->on($model->getTable())->cascadeOnDelete();
                $table->primary(['route_id', $type.'_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accountmanager_route_permission');
        Schema::dropIfExists('accountmanager_route_role');
        Schema::dropIfExists('accountmanager_routes');
    }
};
