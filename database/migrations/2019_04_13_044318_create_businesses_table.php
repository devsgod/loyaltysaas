<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('industry_id')->unsigned()->nullable();
            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('set null');
            $table->string('industry', 128)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_online_business')->default(false);
            $table->string('name', 64)->nullable();
            $table->text('short_description')->nullable();
            $table->mediumText('description')->nullable();
            $table->string('email', 128)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('website', 250)->nullable();
            $table->string('fax', 32)->nullable();
            $table->string('vat_number', 250)->nullable();
            $table->string('id_number', 250)->nullable();
            $table->string('bank', 250)->nullable();
            $table->string('bank_id', 250)->nullable();
            $table->string('ecode_swift', 250)->nullable();
            $table->string('iban', 250)->nullable();
            $table->string('street1', 250)->nullable();
            $table->string('street2', 250)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('state', 64)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->tinyInteger('zoom')->nullable();
            $table->decimal('lat', 17, 14)->nullable();
            $table->decimal('lng', 18, 15)->nullable();
            $table->json('content')->nullable();
            $table->json('social')->nullable();
            $table->json('settings')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('updated_by')->nullable()->unsigned();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `businesses` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('businesses');
    }
}
