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
            $table->string('unit_code', 255)->default(null)->nullable();
            $table->integer('unit_type_count')->default(0);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('user_units_id');
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');


            $table->index(["user_units_id"], 'user_units_id_EFJa_ACYS');
            $table->foreign('user_units_id', 'user_units_id_EFJa_ACYS')->references('id')->on('user_units')->onDelete('cascade');

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
