<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
          
            $table->engine = 'InnoDB';

            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->nullable()->index();
            $table->foreign('account_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('campaign_id')->unsigned()->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->tinyInteger('role')->unsigned()->default(1);
            $table->boolean('active')->default(true);
            $table->string('remote_customer_id', 200)->nullable();
            $table->string('previous_remote_customer_id', 200)->nullable();
            $table->string('customer_number', 32)->nullable();
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
            $table->tinyInteger('first_day_of_week')->nullable();

            $table->ipAddress('signup_ip_address')->nullable();
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
            $table->string('lead_source', 250)->nullable();
            $table->string('lead_source_module', 128)->nullable();
            $table->string('lead_status', 128)->nullable();
            $table->string('lead_type', 128)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('website', 250)->nullable();
            $table->string('fax', 32)->nullable();
            $table->string('street1', 250)->nullable();
            $table->string('street2', 250)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('state', 64)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->nullable();

            // Location
            $table->tinyInteger('zoom')->nullable();
            $table->decimal('lat', 17, 14)->nullable();
            $table->decimal('lng', 18, 15)->nullable();

            $table->string('company', 96)->nullable();
            $table->string('shipping_street1', 250)->nullable();
            $table->string('shipping_street2', 250)->nullable();
            $table->string('shipping_city', 64)->nullable();
            $table->string('shipping_state', 64)->nullable();
            $table->string('shipping_postal_code', 32)->nullable();
            $table->char('shipping_country_code', 2)->nullable();

            // Shipping location
            $table->tinyInteger('shipping_zoom')->nullable();
            $table->decimal('shipping_lat', 17, 14)->nullable();
            $table->decimal('shipping_lng', 18, 15)->nullable();

            $table->string('vat_number', 250)->nullable();
            $table->string('id_number', 250)->nullable();
            $table->string('bank', 250)->nullable();
            $table->string('bank_id', 250)->nullable();
            $table->string('ecode_swift', 250)->nullable();
            $table->string('iban', 250)->nullable();
            $table->mediumText('notes')->nullable();

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

        DB::statement('ALTER TABLE `customers` ADD `uuid` BINARY(16) UNIQUE NULL AFTER `id`;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
