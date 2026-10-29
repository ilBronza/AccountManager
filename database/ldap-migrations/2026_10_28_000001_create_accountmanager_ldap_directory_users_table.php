<?php

use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $model = User::getProjectClassName();
        Schema::connection((new $model)->getConnectionName())->create('accountmanager_ldap_directory_users', function (Blueprint $table) {
            $table->id();
            $table->string('connection', 64);
            $table->uuid('guid');
            $table->string('samaccountname')->nullable();
            $table->string('userprincipalname', 512)->nullable();
            $table->string('name', 512)->nullable();
            $table->string('email', 512)->nullable();
            $table->text('dn')->nullable();
            $table->boolean('enabled');
            $table->boolean('in_required_group');
            $table->boolean('missing')->default(false);
            $table->uuid('import_token');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['connection', 'guid'], 'am_ldap_inventory_directory_guid_unique');
            $table->index(['connection', 'missing'], 'am_ldap_inventory_directory_missing_index');
        });
    }

    public function down(): void
    {
        $model = User::getProjectClassName();
        Schema::connection((new $model)->getConnectionName())->dropIfExists('accountmanager_ldap_directory_users');
    }
};
