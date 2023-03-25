<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackingOrderRequestsTable extends Migration
{
    public $set_schema_table = 'packing_order_requests';
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
            $table->unsignedInteger('order_from_user_id');
            $table->integer('quantity')->default(0);
            $table->string('order_status', 50)->default('Unfinished')->comment('Finished | Unfinished');


            $table->index(["order_from_user_id"], 'order_from_user_id_oeos');
            $table->foreign('order_from_user_id', 'order_from_user_id_oeos')->references('id')->on('users')->onDelete('cascade');

            $table->nullableTimestamps();
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
