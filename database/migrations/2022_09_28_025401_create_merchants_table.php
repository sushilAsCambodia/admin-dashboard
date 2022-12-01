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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('code')->unique();
            $table->unsignedBigInteger('bet_limit_id')->comment('Ref on bet Limits');
            $table->unsignedBigInteger('market_id')->comment('Ref on markets');
            $table->unsignedBigInteger('currency_id')->comment('Ref on currencies');
            $table->string('secret_key');
            $table->string('token');
            $table->double('credit_limit', 10, 2)->default('0.0');
            $table->string('description')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('market_id')->references('id')->on('markets');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('bet_limit_id')->references('id')->on('bet_limits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchants');
    }
};
