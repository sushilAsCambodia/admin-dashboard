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
        Schema::create('ticket_slaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->comment('Ref on tickets');
            $table->unsignedBigInteger('merchant_id')->comment('Ref on merchants');
            $table->unsignedBigInteger('game_play_id')->comment('Ref on game_play');
            $table->string('child_ticket_no');
            $table->string('lottery_number');
            $table->double('big_bet_amount', 20, 8)->default('0.0');
            $table->double('small_bet_amount', 20, 8)->default('0.0');
            $table->double('three_a_amount', 20, 8)->default('0.0');
            $table->double('three_c_amount', 20, 8)->default('0.0');
            $table->double('bet_amount', 20, 8)->default('0.0');
            $table->double('bet_net_amount', 20, 8)->default('0.0');
            $table->double('rebate_amount', 20, 8)->default('0.0');
            $table->double('rebate_percentage', 6, 2)->default('0.0');
            $table->double('big_3a_amount', 20, 8)->default('0.0');
            $table->double('small_3c_amount', 20, 8)->default('0.0');
            $table->enum('game_type', ['3D', '4D']);
            // $table->enum('prize_type', ['No','P1', 'P2', 'P3', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9']);
            $table->enum('prize_type', ['No', 'P1', 'P2', 'P3', 'S', 'C']);
            $table->enum('bet_size', ['Small', 'Big', '3A', '3C', 'Both']);
            $table->double('winning_amount', 20, 8)->default('0.0');
            $table->enum('status', ['in-process', 'finished', 'deleted']);
            $table->enum('progress_status', ['IN_PROGRESS', 'ACCEPTED', 'PARTIALLY_ACCEPTED', 'REJECTED', 'DELETED']);
            $table->string('message');
            $table->date('betting_date');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->foreign('merchant_id')->references('id')->on('merchants');
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
        Schema::dropIfExists('ticket_slaves');
    }
};
