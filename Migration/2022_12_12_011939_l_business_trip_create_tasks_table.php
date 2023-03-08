<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripTasks extends Migration {
    public function up(){
        Schema::create('l_business_trip_tasks', function (Blueprint $table){
            $table->id();
            $table->enum('status',['created','approving', 'fixing_problem', 'signing', 'working', 'archived', 'canceled', 'completed']);
            $table->integer('responsible_id');
            $table->integer('company_id');
            $table->integer('department_id');
            $table->string('position');
            $table->string('checking_account');
            $table->text('comment')->nullable();
            $table->integer('city_start_id');
            $table->integer('city_final_id');
            $table->date('date_start');
            $table->date('date_final');
            $table->boolean('auto_travel');
            $table->string('mark')->nullable();
            $table->string('model')->nullable();
            $table->string('number')->nullable();
            $table->float('gasoline')->nullable();
            $table->integer('back_distance')->nullable();
            $table->string('document_link')->nullable();

            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('l_business_trip_tasks');
    }
};
