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
        Schema::create('limit_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bet_limit_id')->comment('Ref on Bet Limit');
            $table->unsignedBigInteger('game_play_id')->comment('Ref on Game Play');
            $table->integer('big_min_bet')->default(0);
            $table->integer('big_max_bet')->default(0);
            $table->integer('small_min_bet')->default(0);
            $table->integer('small_max_bet')->default(0);
            $table->integer('three_c_min_bet')->default(0);
            $table->integer('three_c_max_bet')->default(0);
            $table->integer('three_a_min_bet')->default(0);
            $table->integer('three_a_max_bet')->default(0);
            $table->integer('game_limit_big')->default(0);
            $table->integer('game_limit_small')->default(0);
            $table->integer('game_limit_three_a')->default(0);
            $table->integer('game_limit_three_c')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('bet_limit_id')->references('id')->on('bet_limits');
            $table->foreign('game_play_id')->references('id')->on('game_plays');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('limit_settings');
    }
};
