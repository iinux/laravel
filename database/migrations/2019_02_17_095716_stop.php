<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Stop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_12306', 16);
            $table->string('code_12306', 16);
            $table->string('pinyin', 32);
            $table->string('pinyin_short', 16);
            $table->string('name', 32);
            $table->string('lng', 16);
            $table->string('lat', 16);
            $table->string('province', 32);
            $table->string('city', 32);
            $table->string('district', 32);
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
        Schema::dropIfExists('stops');
    }
}
