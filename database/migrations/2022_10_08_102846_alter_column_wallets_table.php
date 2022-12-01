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
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('customer_id');
                $table->unsignedBigInteger('member_id')->comment('Ref on members')->after('merchant_id');

                $table->double('winning_balance', 20, 8)->default('0.0');
                $table->double('cashback_balance', 20, 8)->default('0.0');
                $table->foreign('member_id')->references('id')->on('members');
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
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropForeign(['member_id']);
                $table->dropColumn('winning_balance', 15, 4);
                $table->dropColumn('cashback_balance', 15, 4);
            });
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('member_id');
                $table->unsignedBigInteger('customer_id')->comment('Ref on members')->after('merchant_id');

                $table->foreign('customer_id')->references('id')->on('members');
            });
        }
    }
};
