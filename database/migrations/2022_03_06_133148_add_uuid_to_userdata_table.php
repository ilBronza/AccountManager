<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        return ;
        // Schema::table(config('accountmanager.models.userdata.table'), function (Blueprint $table) {
        //     $table->uuid('id')->first()->nullable();
        //     //
        // });

        // foreach(config('accountmanager.models.userdata.class')::all() as $userdata)
        // {
        //     $userdata->id = Str::uuid()->toString();
        //     $userdata->save();            
        // }

        // Schema::table(config('accountmanager.models.userdata.table'), function (Blueprint $table) {
        //     $table->uuid('id')->first()->nullable(false)->change();
        //     //
        // });

        // Schema::table(config('accountmanager.models.userdata.table'), function (Blueprint $table) {
        //     $table->uuid('id')->primary()->change();
        //     //
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table(config('accountmanager.models.userdata.table'), function (Blueprint $table) {
        //     //
        // });
    }
};
