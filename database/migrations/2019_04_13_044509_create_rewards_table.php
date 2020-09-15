<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rewards', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->string('title', 96);
            $table->mediumText('description')->nullable();
            $table->integer('points_cost')->unsigned();
            $table->integer('reward_value')->nullable()->unsigned();
            $table->integer('number_of_times_redeemed')->default(0)->unsigned();
            $table->dateTime('active_from');
            $table->dateTime('expires_at');

            $table->string('language', 5)->nullable();
            $table->char('currency_code', 3)->nullable();

            $table->json('settings')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('updated_by')->nullable()->unsigned();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `rewards` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');

        // Many-to-many relation
        Schema::create('campaign_reward', function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('campaign_id')->unsigned()->index();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->bigInteger('reward_id')->unsigned()->index();
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_reward');
        Schema::dropIfExists('rewards');
    }
}
