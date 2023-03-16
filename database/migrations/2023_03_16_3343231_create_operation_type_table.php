<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperationTypeTable extends Migration
{
    public $set_schema_table = 'operation_type';
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
            $table->string('type_en', 191)->default(null)->comment('operation in en');
            $table->string('type_ar', 191)->default(null)->comment('operation in ar');
            $table->unsignedInteger('operation_id');
            $table->unsignedInteger('add_by_user_id');

            $table->index(["operation_id"], 'operation_id_rueq_dax');
            $table->index(["add_by_user_id"], 'add_by_user_id_relat_iw');
            $table->foreign('operation_id', 'operation_id_rueq_dax')->references('id')->on('operations')->onDelete('cascade');
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
