<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChPrice extends Migration
{
    protected $tableName = 'ch_prices';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(10)->comment("10new20history");
            $table->string('from');
            $table->string('to');
            $table->string('date', 10);
            $table->integer('price');
            $table->string('heartbeat', 32);
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
        //
        Schema::dropIfExists($this->tableName);
    }
}
