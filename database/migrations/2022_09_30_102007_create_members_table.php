<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->comment('Ref on merchants');
            $table->string('customer_id');
            $table->string('customer_name')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('temp_token')->nullable();
            $table->string('last_login')->nullable();
            $table->timestamp('login_ip')->useCurrent();
            $table->string('online_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('merchant_id')->references('id')->on('merchants');
            //$table->unique(["customer_id", "merchant_id"], 'customer_merchant_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('members');
    }
};
