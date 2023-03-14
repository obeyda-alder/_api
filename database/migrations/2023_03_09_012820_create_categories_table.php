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
            $table->integer('from')->default(0);
            $table->integer('to')->default(0);
            $table->decimal('price', 7, 2)->default(0);
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');

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
