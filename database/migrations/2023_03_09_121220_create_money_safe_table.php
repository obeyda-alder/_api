<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoneySafeTable extends Migration
{
    public $set_schema_table = 'money_safe';
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
            $table->integer('amount')->default(0);
            $table->unsignedInteger('user_id');
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | NOT_ACTIVE');

            $table->index(["user_id"], 'user_id_EFJa_ACXX');
            $table->foreign('user_id', 'user_id_EFJa_ACXX')->references('id')->on('users')->onDelete('cascade');
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
