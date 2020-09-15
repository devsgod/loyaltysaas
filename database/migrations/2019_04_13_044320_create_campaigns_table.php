<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('business_id')->unsigned()->nullable()->index();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('set null');
            $table->boolean('active')->default(true);
            $table->string('slug', 128)->unique()->nullable()->index();
            $table->string('host', 128)->index()->nullable();
            $table->string('ssl_app_id', 64)->nullable();
            $table->string('name', 96);
            $table->mediumText('description')->nullable();
            $table->bigInteger('signup_bonus_points')->unsigned()->default(0);
            $table->integer('points_expire_days')->unsigned()->default(0);
            $table->integer('points_expire_weeks')->unsigned()->default(0);
            $table->integer('points_expire_months')->unsigned()->default(0);
            $table->integer('points_expire_years')->unsigned()->default(0);
            $table->string('language', 5)->nullable();
            $table->json('content')->nullable();
            $table->json('settings')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();

            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('updated_by')->nullable()->unsigned();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `campaigns` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
}
