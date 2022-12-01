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
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id');
                $table->string('external_transaction_id')->nullable();
                $table->unsignedBigInteger('customer_id')->comment('Ref on members');
                $table->foreign('customer_id')->references('id')->on('members');
                $table->unsignedBigInteger('merchant_id')->comment('Ref on merchants');
                $table->foreign('merchant_id')->references('id')->on('merchants');
                $table->enum('transaction_type', ['Debit', 'Credit']);
                //$table->enum('transaction_from', ['front', 'backend', 'winning', 'cashback']);
                $table->enum('transaction_from', ['transfer-in', 'transfer-out', 'pay-out', 'betting', 'rebate']);
                $table->string('amount');
                $table->string('currency');
                $table->text('message');
                $table->enum('status', ['In-process', 'Complete', 'Fail']);
                $table->softDeletes();
                $table->timestamps();
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
        Schema::dropIfExists('transactions');
    }
};
