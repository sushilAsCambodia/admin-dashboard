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
        Schema::create('odd_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->unsignedBigInteger('game_play_id');
            $table->mediumInteger('big_first');
            $table->mediumInteger('big_second');
            $table->mediumInteger('big_third');
            $table->mediumInteger('big_special');
            $table->mediumInteger('big_consolation');
            $table->mediumInteger('small_first');
            $table->mediumInteger('small_second');
            $table->mediumInteger('small_third');
            $table->mediumInteger('three_c_first');
            $table->mediumInteger('three_c_second');
            $table->mediumInteger('three_c_third');
            $table->mediumInteger('three_a_first');
            $table->mediumInteger('rebate_4d');
            $table->mediumInteger('rebate_3d');
            $table->softDeletes();
            $table->timestamps();

            // composite key
            $table->unique(['market_id', 'game_play_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('odd_settings');
    }
};
