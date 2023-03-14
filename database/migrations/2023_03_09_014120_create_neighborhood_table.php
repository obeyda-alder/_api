<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNeighborhoodTable extends Migration
{
    public $set_schema_table = 'neighborhoods';
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
            $table->unsignedInteger('municipality_id');
            $table->string('name_en', 150)->default('');
            $table->string('name_ar', 250);
            $table->string('neighborhood_key', 100)->default('');
            $table->string('neighborhood_municipality_key', 10)->default('');
            $table->string('latitude', 45)->nullable()->default(null);
            $table->string('longitude', 45)->nullable()->default(null);
            $table->string('google_short_name', 100);
            $table->string('google_long_name', 100);
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
