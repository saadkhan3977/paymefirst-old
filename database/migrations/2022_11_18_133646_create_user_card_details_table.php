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
        Schema::create('user_card_details', function (Blueprint $table) {
            $table->id();
            $table->integer('card_name')->nullable();
            $table->integer('card_number')->nullable();
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->integer('cvv')->nullable();
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
        Schema::dropIfExists('user_card_details');
    }
};
