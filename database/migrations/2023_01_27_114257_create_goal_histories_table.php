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
        Schema::create('goal_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('amount_save')->nullable();
            $table->string('plan')->nullable();
            $table->string('starting_date')->nullable();
            $table->string('ending_date')->nullable();
            $table->string('number_deduction')->nullable();
            $table->string('cnd')->nullable();
            $table->string('amount_per_deduction')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goal_histories');
    }
};
