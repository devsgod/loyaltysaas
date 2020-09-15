<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('codes', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('campaign_id')->unsigned()->index();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->bigInteger('customer_id')->unsigned()->nullable()->index();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->bigInteger('staff_id')->unsigned()->nullable()->index();
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
            $table->string('staff_name', 64)->nullable();
            $table->string('staff_email', 128)->nullable();
            $table->bigInteger('reward_id')->nullable()->unsigned()->index();
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('set null');

            $table->string('type', 32);
            $table->string('code', 250);
            $table->integer('number_of_times_code_can_be_used')->nullable()->unsigned();
            $table->integer('number_of_times_code_has_been_used')->nullable()->unsigned();
            $table->bigInteger('points')->nullable()->unsigned();
            $table->dateTime('expires_at')->nullable();

            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('updated_by')->nullable()->unsigned();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `codes` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');

        // Many-to-many relation
        Schema::create('code_segment', function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('code_id')->unsigned()->index();
            $table->foreign('code_id')->references('id')->on('codes')->onDelete('cascade');
            $table->bigInteger('segment_id')->unsigned()->index();
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('code_segment');
        Schema::dropIfExists('codes');
    }
}
