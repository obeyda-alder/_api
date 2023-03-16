<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    public $set_schema_table = 'categories';
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
            $table->string('name', 191);
            $table->string('code', 191);
            $table->integer('unit_min_limit')->default(0);
            $table->integer('unit_max_limit')->default(0);
            $table->decimal('value_in_price', 7, 2)->default(0);
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');
            $table->integer('percentage')->default(0);
            $table->unsignedInteger('operation_type_id');
            $table->unsignedInteger('add_by_user_id');

            $table->index(["operation_type_id"], 'operation_type_id_FAT_EDC_ZXC');
            $table->index(["add_by_user_id"], 'add_by_user_id_EL_CVX');
            $table->foreign('operation_type_id', 'operation_type_id_FAT_EDC_ZXC')->references('id')->on('operation_type')->onDelete('cascade');
            $table->foreign('add_by_user_id', 'add_by_user_id_EL_CVX')->references('id')->on('users')->onDelete('cascade');
            $table->unique(["code"], 'categories_code_unique');
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
