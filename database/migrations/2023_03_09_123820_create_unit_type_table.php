<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitTypeTable extends Migration
{
    public $set_schema_table = 'unit_type';
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
            $table->string('type', 191)->default(null)->comment('unit type');
            $table->unsignedInteger('relation_id');
            $table->unsignedInteger('add_by_user_id');

            $table->index(["relation_id"], 'relation_id_unit_type_PAO');
            $table->foreign('relation_id', 'relation_id_unit_type_PAO')->references('id')->on('relations_type')->onDelete('cascade');

            $table->index(["add_by_user_id"], 'add_by_user_id_units_iwas');
            $table->foreign('add_by_user_id', 'add_by_user_id_units_iwas')->references('id')->on('users')->onDelete('cascade');
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
