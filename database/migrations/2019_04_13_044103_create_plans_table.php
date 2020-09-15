<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('name', 64);
            $table->tinyInteger('role')->unsigned()->default(3);
            $table->string('color', 24)->nullable();
            $table->text('description')->nullable();
            $table->string('language', 5)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->integer('price')->unsigned()->nullable();
            $table->string('billing_interval', 32)->nullable()->default('month');
            $table->string('remote_product_id', 128)->nullable();
            $table->string('product_id_paddle', 128)->nullable();
            $table->string('product_id_2checkout', 128)->nullable();
            $table->string('product_id_stripe', 128)->nullable();
            $table->string('product_id_remote1', 128)->nullable();
            $table->string('product_id_remote2', 128)->nullable();
            $table->string('product_id_remote3', 128)->nullable();
            $table->string('product_id_remote4', 128)->nullable();
            $table->string('product_id_remote5', 128)->nullable();
            $table->json('limitations')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->integer('created_by')->nullable()->unsigned();
            $table->integer('updated_by')->nullable()->unsigned();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `plans` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
}
