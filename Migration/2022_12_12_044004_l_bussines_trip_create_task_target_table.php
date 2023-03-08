<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripTaskTarget extends Migration {
    public function up()
    {
        Schema::create('l_business_trip_task_target', function (Blueprint $table) {
            $table->id();
            $table->integer('target_id');
            $table->integer('dot_id');
            $table->integer('task_id');
            $table->integer('sort');
            $table->string('contractor');
            $table->string('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_task_target');
    }
};
