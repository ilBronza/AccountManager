<?php

use App\Models\User;
use IlBronza\AccountManager\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PopulateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach(['superadmin', 'administrator', 'editor'] as $type)
            $role = Role::create([
                'name' => $type,
                'guard_name' => 'web'
            ]);

        if(! $user = User::find(1))
            $user = User::make()->id = 1;

        $user->assignRole('superadmin');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
