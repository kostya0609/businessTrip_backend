<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripNeedAction extends Migration {
    public function up(){
        Schema::create('l_business_trip_need_action', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('task_id');
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_need_action');
    }
};
