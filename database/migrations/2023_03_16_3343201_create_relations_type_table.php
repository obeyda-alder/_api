<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationsTypeTable extends Migration
{
    public $set_schema_table = 'relations_type';
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
            $table->string('type', 191)->default(null)->comment('relation type');
            $table->unsignedInteger('add_by_user_id');

            $table->unique(["type"], 'type_unique');
            $table->index(["add_by_user_id"], 'add_by_user_id_relat_iwas');
            $table->foreign('add_by_user_id', 'add_by_user_id_relat_iwas')->references('id')->on('users')->onDelete('cascade');
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
