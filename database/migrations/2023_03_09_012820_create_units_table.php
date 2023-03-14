<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitsTable extends Migration
{
    public $set_schema_table = 'units';
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
            $table->decimal('price', 7, 2)->default(0);
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('category_id')->nullable()->default(null);
            $table->unique(["code"], 'units_code_unique');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id', 'user_id_dax_iw')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id', 'category_id_we_osa')->references('id')->on('categories')->onDelete('cascade');
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
