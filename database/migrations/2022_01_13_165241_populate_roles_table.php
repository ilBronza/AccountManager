<?php

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PopulateRolesTable extends Migration
{
    public function createMainUser()
    {
        DB::table('users')
            ->insert([
                'name' => 'bronza',
                'email' => 'bronza.dogodesign@gmail.com',
                'password' => Hash::make('qweqweqwe')]);

        return User::getProjectClassName()::where('name', 'bronza')->first();
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach([
            'superadmin',
            'administrator',
            'editor'
        ] as $type)
            $role = Role::getProjectClassName()::create([
                'name' => $type,
                'guard_name' => 'web'
            ]);

        if(! $user = User::getProjectClassName()::find(1))
            $user = $this->createMainUser();

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
