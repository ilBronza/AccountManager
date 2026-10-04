<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateUsersRoleAndAssignToUser1 extends Migration
{
    public function up()
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'accountManagerUsers', 'guard_name' => 'web',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down()
    {
        // Keep roles and assignments, which may predate this migration.
    }
}
