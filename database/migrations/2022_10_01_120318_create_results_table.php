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
        if (! Schema::hasTable('results')) {
            Schema::create('results', function (Blueprint $table) {
                $table->id();
                //$table->unsignedBigInteger('merchant_id')->comments('Ref on merchants');
                //$table->foreign('merchant_id')->references('id')->on('merchants');
                //$table->unsignedBigInteger('product_id')->comments('Ref on products');
                //$table->foreign('product_id')->references('id')->on('products');
                $table->unsignedBigInteger('game_play_id')->comments('Ref on game_play');
                //$table->foreign('game_play_id')->references('id')->on('game_play');
                $table->string('fetching_date')->nullable();
                //$table->date('fetching_date_new');
                //$table->string('title');
                $table->string('result_date')->nullable();
                $table->string('reference_number')->nullable();
                $table->string('prize1');
                $table->string('prize2');
                $table->string('prize3');
                $table->string('special1');
                $table->string('special2');
                $table->string('special3');
                $table->string('special4');
                $table->string('special5');
                $table->string('special6');
                $table->string('special7');
                $table->string('special8');
                $table->string('special9');
                $table->string('special10');
                $table->string('consolation1');
                $table->string('consolation2');
                $table->string('consolation3');
                $table->string('consolation4');
                $table->string('consolation5');
                $table->string('consolation6');
                $table->string('consolation7');
                $table->string('consolation8');
                $table->string('consolation9');
                $table->string('consolation10');
                $table->enum('confirm', ['Yes', 'No']);
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
        Schema::dropIfExists('results');
    }
};
