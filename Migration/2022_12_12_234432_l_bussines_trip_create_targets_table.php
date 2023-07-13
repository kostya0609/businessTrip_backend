<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripTargets extends Migration {

    public function up(){
        Schema::create('l_business_trip_targets', function (Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->boolean('active');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_targets');
    }
};
