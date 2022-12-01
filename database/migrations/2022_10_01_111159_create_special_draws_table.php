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
        if (! Schema::hasTable('special_draws')) {
            Schema::create('special_draws', function (Blueprint $table) {
                $table->id();
                $table->date('draw_date');
                $table->enum('status', ['enabled', 'disabled', 'upcoming', 'ongoing', 'drawn'])->default('upcoming');
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
        Schema::dropIfExists('special_draws');
    }
};
