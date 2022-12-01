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
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->comment('Ref on members');
                $table->foreign('customer_id')->references('id')->on('members');
                $table->unsignedBigInteger('merchant_id')->comment('Ref on merchants');
                $table->foreign('merchant_id')->references('id')->on('merchants');
                $table->string('amount');
                $table->enum('status', ['Active', 'In-active']);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallets');
    }
};
