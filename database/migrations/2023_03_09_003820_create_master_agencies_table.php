<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterAgenciesTable extends Migration
{
    public $set_schema_table = 'master_agencies';
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
            $table->string('first_name', 191);
            $table->string('last_name', 191);
            $table->string('email', 191)->nullable()->default(null);
            $table->unsignedInteger('country_id')->nullable()->default(null);
            $table->unsignedInteger('city_id')->nullable()->default(null);
            $table->unsignedInteger('municipality_id')->nullable()->default(null);
            $table->unsignedInteger('neighborhood_id')->nullable()->default(null);
            $table->string('desc_address')->nullable()->default(null);
            $table->string('latitude', 45)->nullable()->default(null);
            $table->string('longitude', 45)->nullable()->default(null);
            $table->string('iban', 191)->nullable()->default(null);
            $table->string('iban_name', 191)->nullable()->default(null);
            $table->string('iban_type', 191)->nullable()->default(null);
            $table->string('phone_number', 100);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('has_sub_agent')->default(0)->comment('0 | 1');
            $table->integer('sub_agent_count')->default(0);
            $table->string('status', 50)->default('ACTIVE')->comment('ACTIVE | SUSPENDED | PENDING');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(["phone_number"], 'master_agen_phone_number_unique');
            $table->unique(["email"], 'master_agen_email_unique');

            $table->index(["city_id"], 'master_agen_city_id_index');
            $table->foreign('city_id', 'master_agen_city_id_index')->references('id')->on('cities')->onDelete('set null')->onUpdate('restrict');

            $table->index(["country_id"], 'master_agent_country_id_index');
            $table->foreign('country_id', 'master_agent_country_id_index')->references('id')->on('countries')->onDelete('set null')->onUpdate('restrict');

            $table->index(["user_id"], 'master_agent_user_id_index');
            $table->foreign('user_id', 'master_agent_user_id_index')->references('id')->on('users')->onDelete('cascade');
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
