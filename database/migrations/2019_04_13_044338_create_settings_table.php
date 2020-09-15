<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->bigInteger('account_id')->unsigned()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('user_id')->unsigned();
            $table->string('name', 250)->nullable();
            $table->string('value_string', 250)->nullable();
            $table->mediumText('value_text')->nullable();
            $table->integer('value_int')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamp('value_date_time')->nullable();
            $table->date('value_date')->nullable();
            $table->time('value_time')->nullable();
            $table->json('value_json')->nullable();
            $table->ipAddress('value_ip_address')->nullable();
        });

        DB::statement('ALTER TABLE `settings` ADD `uuid` BINARY(16) NULL AFTER `user_id`;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
