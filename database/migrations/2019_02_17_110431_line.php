<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Line extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lines', function (Blueprint $table) {
            $table->increments('id');
            $table->string('train_class', 8);
            $table->string('train_code', 8);
            $table->string('start_station_name', 32);
            $table->string('end_station_name', 32);
            $table->string('train_no', 32);
            $table->string('date', 16)->nullable();
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
        Schema::dropIfExists('lines');
    }
}
