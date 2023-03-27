<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationUnitTypeWithOperationsTable extends Migration
{
    public $set_schema_table = 'relation_unit_type_with_operations';
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
            $table->unsignedInteger('from_unit_type_id');
            $table->unsignedInteger('to_unit_type_id');
            $table->unsignedInteger('operation_id');
            $table->unsignedInteger('add_by_user_id');


            $table->index(["from_unit_type_id"], 'from_unit_type_id_indexes');
            $table->foreign('from_unit_type_id', 'from_unit_type_id_indexes')->references('id')->on('unit_type')->onDelete('cascade');

            $table->index(["to_unit_type_id"], 'to_unit_type_id_indexes');
            $table->foreign('to_unit_type_id', 'to_unit_type_id_indexes')->references('id')->on('unit_type')->onDelete('cascade');


            $table->index(["operation_id"], 'operation_id_rueq_dax');
            $table->foreign('operation_id', 'operation_id_rueq_dax')->references('id')->on('operations')->onDelete('cascade');

            $table->index(["add_by_user_id"], 'add_by_user_id_relat_iw');
            $table->foreign('add_by_user_id', 'add_by_user_id_relat_iw')->references('id')->on('users')->onDelete('cascade');
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
