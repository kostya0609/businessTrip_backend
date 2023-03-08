<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripCostUnits extends Migration {

    public function up(){
        Schema::create('l_business_trip_cost_units', function (Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('name_unit');
            $table->integer('unit_price');
            $table->integer('limit_cost_unit');
            $table->boolean('daily_allowance');

            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_cost_units');
    }
};
