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
        Schema::create('ticket_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->comment('Ref on tickets');
            $table->unsignedBigInteger('member_id')->comment('Ref on members');
            $table->unsignedBigInteger('transaction_id')->comment('Ref on transactions');
            $table->double('amount', 20, 8)->default('0.0');
            $table->enum('type', ['Winning', 'Rebate']);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->foreign('member_id')->references('id')->on('members');
            //$table->foreign('transaction_id')->references('id')->on('transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_settlements');
    }
};
