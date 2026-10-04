<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PopulateRolesTable extends Migration
{
    public function up()
    {
        Schema::table('roles', fn (Blueprint $table) => $table->softDeletes());
        foreach (['superadmin', 'administrator', 'editor'] as $name) {
            DB::table('roles')->insertOrIgnore([
                'name' => $name, 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Roles may already be assigned or customized. Never delete application data.
        Schema::table('roles', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
}
