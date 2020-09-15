<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->nullable()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('role')->unsigned()->default(1);
            $table->boolean('active')->default(true);
            $table->string('name', 64)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('verification_code', 64)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('token')->nullable();
            $table->string('login_code', 64)->nullable();
            $table->timestamp('login_code_valid_until')->nullable();
            $table->string('language', 5)->nullable();
            $table->string('locale', 24)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('timezone', 32)->nullable();
            $table->integer('logins')->default(0)->unsigned();
            $table->dateTime('last_login')->nullable();
            $table->ipAddress('last_login_ip_address')->nullable();
            $table->dateTime('expires')->nullable();
            $table->string('salutation', 32)->nullable();
            $table->string('first_name', 64)->nullable();
            $table->string('last_name', 64)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->string('job_title', 64)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_business', 32)->nullable();
            $table->string('phone_personal', 32)->nullable();
            $table->string('mobile_business', 32)->nullable();
            $table->string('mobile_personal', 32)->nullable();
            $table->string('website', 250)->nullable();
            $table->string('fax', 32)->nullable();
            $table->string('street1', 250)->nullable();
            $table->string('street2', 250)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('state', 64)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->mediumText('notes')->nullable();
            $table->json('social')->nullable();
            $table->json('settings')->nullable();
            $table->json('tags')->nullable();
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();

            $table->bigInteger('created_by')->unsigned()->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('updated_by')->nullable()->unsigned();

            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE `staff` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');

        // Many-to-many relation
        Schema::create('business_staff', function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('business_id')->unsigned()->index();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_staff');
        Schema::dropIfExists('staff');
    }
}
