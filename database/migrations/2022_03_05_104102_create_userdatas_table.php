<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserdatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('accountmanager.models.userdata.table'), function (Blueprint $table) {

            $table->foreignId('user_id')->nullable()->constrained();

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('accountmanager.models.userdata.table'));
    }
}
