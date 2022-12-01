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
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('login_ip');
            });
            Schema::table('members', function (Blueprint $table) {
                $table->string('login_ip')->nullable();
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
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->datetime('login_ip')->change();
            });
        }
    }
};
