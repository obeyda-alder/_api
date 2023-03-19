<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserUnitsTable extends Migration
{
    public $set_schema_table = 'user_units';
    /**
     * Run the migrations.
     *
     * @return void
    */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('unit_type_id');
            $table->unsignedInteger('user_id');
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');


            $table->index(["unit_type_id"], 'unit_type_id_EEUSJ');
            $table->foreign('unit_type_id', 'unit_type_id_EEUSJ')->references('id')->on('unit_type')->onDelete('cascade');

            $table->index(["user_id"], 'user_id_ERT_SDE');
            $table->foreign('user_id', 'user_id_ERT_SDE')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->set_schema_table);
    }
}
