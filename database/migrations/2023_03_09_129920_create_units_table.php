<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitsTable extends Migration
{
    public $set_schema_table = 'unit_generation_history';
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
            $table->string('unit_code', 191)->default(null)->comment('unit type');
            $table->integer('unit_value')->default(0);
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');
            $table->decimal('price', 7, 2)->default(0);
            $table->unsignedInteger('add_by');
            $table->unsignedInteger('unit_type_id');



            $table->unique(["unit_code"], 'unit_code_er_unique');

            $table->index(["unit_type_id"], 'unit_type_id_SAWW_RW');
            $table->foreign('unit_type_id', 'unit_type_id_SAWW_RW')->references('id')->on('unit_type')->onDelete('cascade');

            $table->index(["add_by"], 'add_by_EFJa_ACXX');
            $table->foreign('add_by', 'add_by_EFJa_ACXX')->references('id')->on('users')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
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
