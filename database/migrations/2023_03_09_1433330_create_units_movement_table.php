<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitsMovementTable extends Migration
{
    public $set_schema_table = 'units_movement';
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
            $table->string('unit_code', 191)->default(null)->comment('money code');
            $table->text('transfer_type', 300)->default(null)->comment('reason type to transfer actions');
            $table->decimal('quantity', 7, 2)->default(0);
            $table->string('status', 50)->default('ADD')->comment('ADD | INCREASE | DECREASE');
            $table->unsignedInteger('to_user_id');
            $table->unsignedInteger('from_user_id');


            $table->index(["to_user_id"], 'to_user_id_trans_peod_');
            $table->foreign('to_user_id', 'to_user_id_trans_peod_')->references('id')->on('users')->onDelete('cascade');

            $table->index(["from_user_id"], 'from_user_id_trans_peod_');
            $table->foreign('from_user_id', 'from_user_id_trans_peod_')->references('id')->on('users')->onDelete('cascade');

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
