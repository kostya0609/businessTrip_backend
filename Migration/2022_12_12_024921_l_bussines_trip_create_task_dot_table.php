<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripTaskDot extends Migration {
    public function up(){
        Schema::create('l_business_trip_task_dot', function (Blueprint $table){
            $table->id();
            $table->integer('city_id');
            $table->integer('task_id');
            $table->integer('days');
            $table->integer('sort');
            $table->integer('distance')->nullable();
            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_task_dot');
    }
};
