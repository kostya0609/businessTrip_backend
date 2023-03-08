<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripCities extends Migration {

    public function up(){
        Schema::create('l_business_trip_cities', function (Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('region');
            $table->integer('population');
            $table->integer('user_id');
            $table->boolean('active');
            $table->integer('limit_km')->nullable();
            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_cities');
    }
};
