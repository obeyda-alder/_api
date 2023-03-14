<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    public $set_schema_table = 'countries';
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
            $table->string('sortname', 3)->default('');
            $table->string('name_en', 150)->default('');
            $table->string('name_ar', 250);
            $table->string('flag', 100)->default('');
            $table->string('code', 10)->default('');
            $table->string('currency', 50);
            $table->string('currency_icon', 50);
            $table->string('status', 5);
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
