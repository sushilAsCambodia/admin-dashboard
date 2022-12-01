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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('Ref on members');
            $table->unsignedBigInteger('merchant_id')->comment('Ref on merchants');
            $table->string('ticket_no');
            $table->string('bet_number')->comment('0-Box,1-Ibox,2-Reverse,3-Others');
            $table->integer('bet_type');
            $table->double('total_amount', 20, 8)->default('0.0');
            $table->double('net_amount', 20, 8)->default('0.0');
            $table->double('rebate_amount', 20, 8)->default('0.0');
            $table->double('rebate_percentage', 6, 2)->default('0.0');
            $table->date('betting_date');
            $table->date('draw_date');
            $table->string('draw_number');
            $table->enum('ticket_status', ['SETTLED', 'UNSETTLED']);
            $table->enum('progress_status', ['IN_PROGRESS', 'ACCEPTED', 'PARTIALLY_ACCEPTED', 'REJECTED', 'DELETED']);
            $table->string('message');
            // $table->string('game_code');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('member_id')->references('id')->on('members');
            $table->foreign('merchant_id')->references('id')->on('merchants');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
