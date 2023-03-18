<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitTypesSafeTable extends Migration
{
    public $set_schema_table = 'unit_types_safe';
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
            $table->integer('unit_type_count')->default(0);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('unit_type_id');
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');


            $table->index(["unit_type_id"], 'unit_type_id_EFJa_ACYS');
            $table->foreign('unit_type_id', 'unit_type_id_EFJa_ACYS')->references('id')->on('unit_type')->onDelete('cascade');

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
