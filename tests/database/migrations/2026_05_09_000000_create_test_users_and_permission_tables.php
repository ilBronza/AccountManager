<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('users', function (Blueprint $table)
		{
			$table->id();
			$table->string('name')->nullable();
			$table->string('email')->nullable()->unique();
			$table->string('password')->nullable();
			$table->boolean('active')->default(true);
			$table->boolean('allow_from_remote')->default(false);
			$table->text('allowed_ips')->nullable();
			$table->string('github_id')->nullable();
			$table->text('github_token')->nullable();
			$table->text('github_refresh_token')->nullable();
			$table->rememberToken();
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('password_reset_tokens', function (Blueprint $table) {
			$table->string('email')->primary();
			$table->string('token');
			$table->timestamp('created_at')->nullable();
		});

		// Minimal Spatie Permission tables (no teams)
		Schema::create('permissions', function (Blueprint $table)
		{
			$table->bigIncrements('id');
			$table->string('name');
			$table->string('guard_name');
			$table->timestamps();
			$table->softDeletes();
			$table->unique(['name', 'guard_name']);
		});

		Schema::create('roles', function (Blueprint $table)
		{
			$table->bigIncrements('id');
			$table->string('name');
			$table->string('guard_name');
			$table->boolean('allow_from_remote')->default(false);
			$table->text('allowed_ips')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->unique(['name', 'guard_name']);
		});

		Schema::create('model_has_permissions', function (Blueprint $table)
		{
			$table->unsignedBigInteger('permission_id');
			$table->string('model_type');
			$table->unsignedBigInteger('model_id');
			$table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
			$table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
		});

		Schema::create('model_has_roles', function (Blueprint $table)
		{
			$table->unsignedBigInteger('role_id');
			$table->string('model_type');
			$table->unsignedBigInteger('model_id');
			$table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
			$table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
			$table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
		});

		Schema::create('role_has_permissions', function (Blueprint $table)
		{
			$table->unsignedBigInteger('permission_id');
			$table->unsignedBigInteger('role_id');
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
			$table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
			$table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
		});

		Schema::create('users__userdata', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->foreignId('user_id')->constrained();
			$table->nullableUuidMorphs('userdatable');
			$table->string('first_name', 64)->nullable();
			$table->string('surname', 64)->nullable();
			$table->string('short_name', 12)->nullable();
			$table->string('fiscal_code', 16)->nullable();
			$table->date('birth_date')->nullable();
			$table->enum('gender', ['m', 'f', 'nd'])->nullable();
			$table->string('avatar')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('media', function (Blueprint $table) {
			$table->id();
			$table->uuidMorphs('model');
			$table->uuid('uuid')->nullable()->unique();
			$table->string('collection_name');
			$table->string('name');
			$table->string('file_name');
			$table->string('mime_type')->nullable();
			$table->string('disk');
			$table->string('conversions_disk')->nullable();
			$table->unsignedBigInteger('size');
			$table->json('manipulations');
			$table->json('custom_properties');
			$table->json('generated_conversions');
			$table->json('responsive_images');
			$table->unsignedInteger('order_column')->nullable();
			$table->boolean('integrity_check')->default(false);
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('datatabledata', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained();
			$table->string('key');
			$table->json('data');
			$table->timestamps();
		});

		Schema::create('user_access_logs', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->timestamp('visited_at');
			$table->string('method', 16);
			$table->text('url');
			$table->string('ip', 45);
			$table->text('user_agent')->nullable();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('user_access_logs');
		Schema::dropIfExists('datatabledata');
		Schema::dropIfExists('media');
		Schema::dropIfExists('users__userdata');
		Schema::dropIfExists('role_has_permissions');
		Schema::dropIfExists('model_has_roles');
		Schema::dropIfExists('model_has_permissions');
		Schema::dropIfExists('roles');
		Schema::dropIfExists('permissions');
		Schema::dropIfExists('password_reset_tokens');
		Schema::dropIfExists('users');
	}
};
