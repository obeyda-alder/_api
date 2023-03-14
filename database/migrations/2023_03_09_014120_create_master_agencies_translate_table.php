<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterAgenciesTranslateTable extends Migration
{
    public $set_schema_table = 'master_agencies_translate';
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
            $table->string('title', 191)->nullable()->default(null);
            $table->string('description', 191)->nullable()->default(null);
            $table->string('locale', 10);
            $table->string('image')->nullable()->default(null);
            $table->unsignedInteger('master_agent_id');
            $table->softDeletes();
            $table->timestamps();


            $table->index(["master_agencies_id"], 'master_agencies_id_fk_idx');
            $table->foreign('master_agencies_id', 'master_agencies_id_fk_idx')->references('id')->on('master_agencies')->onDelete('cascade')->onUpdate('cascade');
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
