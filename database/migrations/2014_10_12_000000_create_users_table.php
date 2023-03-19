<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'users';

    /**
     * Run the migrations.
     * @table users
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('master_agent_user_id')->nullable()->default(null);
            $table->string('type', 100);
            $table->string('name', 191);
            $table->string('username', 191)->nullable()->default(null);
            $table->string('phone_number', 100)->nullable()->default(null);
            $table->string('email', 191);
            $table->string('password', 191);
            $table->string('image', 200)->nullable()->default(null);
            $table->string('verification_code', 191)->nullable()->default(null)->comment('VERIFIED: Verified | HASH: Needs Verification');
            $table->string('status', 50)->default('PENDING')->comment('PENDING | ACTIVE | SUSPENDED | FROZEN');
            $table->unsignedInteger('municipality_id')->nullable()->default(null);
            $table->unsignedInteger('neighborhood_id')->nullable()->default(null);
            $table->unsignedInteger('country_id')->nullable()->default(null);
            $table->unsignedInteger('city_id')->nullable()->default(null);
            $table->string('registration_type', 45)->default('NORMAL')->comment('NORMAL, FACEBOOK, TWITTER, GOOGLE, INTAGRAM');
            $table->string('social_id', 45)->nullable()->default(null);
            $table->rememberToken();
            $table->unique(["username"], 'users_username_unique');
            $table->unique(["phone_number"], 'users_phone_number_unique');
            $table->unique(["email"], 'users_email_unique');
            $table->softDeletes();
            $table->nullableTimestamps();


            $table->index(["master_agent_user_id"], 'master_agent_id_dhu_ud');
            $table->index(["country_id"], 'users_country_id_index');
            $table->index(["city_id"], 'users_city_id_index');
            $table->unique(["username"], 'users_username_unique');
            $table->unique(["phone_number"], 'users_phone_number_unique');
            $table->unique(["email"], 'users_email_unique');
            $table->foreign('city_id', 'users_city_id_index')->references('id')->on('cities')->onDelete('set null')->onUpdate('restrict');
            $table->foreign('country_id', 'users_country_id_index')->references('id')->on('countries')->onDelete('set null')->onUpdate('restrict');
        });

        Schema::table($this->set_schema_table ,function (Blueprint $table){
            $table->foreign('master_agent_user_id')->references('id')->on($this->set_schema_table)->onDelete('set null')->onUpdate('restrict');
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
