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
        Schema::connection((new $model)->getConnectionName())->create('accountmanager_ldap_identities', function (Blueprint $table) {
            $table->id();
            $table->string('connection', 64);
            $table->uuid('guid');
            // Supports integer and UUID keys without altering the host users table.
            $table->string('user_id', 128);
            $table->timestamp('created_at');
            $table->unique(['connection', 'guid'], 'am_ldap_directory_guid_unique');
            $table->unique(['connection', 'user_id'], 'am_ldap_directory_user_unique');
        });
    }

    public function down(): void
    {
        $model = User::getProjectClassName();
        Schema::connection((new $model)->getConnectionName())->dropIfExists('accountmanager_ldap_identities');
    }
};
