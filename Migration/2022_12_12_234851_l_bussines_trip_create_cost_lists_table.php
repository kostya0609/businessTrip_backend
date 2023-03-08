<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripCostLists extends Migration {

    public function up(){
        Schema::create('l_business_trip_cost_lists', function (Blueprint $table){
            $table->id();
            $table->integer('task_id');
            $table->integer('cost_id');
            $table->float('unit_cost');
            $table->float('count_unit_cost');

            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_cost_lists');
    }
};
