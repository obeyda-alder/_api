<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperationsTable extends Migration
{
    public $set_schema_table = 'operations';
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
            $table->string('type_en', 191)->default(null)->comment('operation type in en');
            $table->string('type_ar', 191)->default(null)->comment('operation type in ar');
            $table->unsignedInteger('relation_id');
            $table->unsignedInteger('add_by_user_id');

            $table->index(["relation_id"], 'relation_id_FAT_EDC');
            $table->index(["add_by_user_id"], 'add_by_user_id_FAX_WE');
            $table->foreign('relation_id', 'relation_id_FAT_EDC')->references('id')->on('relations_type')->onDelete('cascade');
            $table->foreign('add_by_user_id', 'add_by_user_id_FAX_WE')->references('id')->on('users')->onDelete('cascade');
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
