<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class YeStop extends Migration
{
    protected $tableName = 'ye_stops';
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
            $table->integer('ye_line_id');
            $table->integer('station_number');
            $table->string('name');
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
        //
        Schema::dropIfExists($this->tableName);
    }
}
