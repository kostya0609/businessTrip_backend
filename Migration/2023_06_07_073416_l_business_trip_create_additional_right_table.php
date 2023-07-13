<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripAdditionalRight extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('l_business_trip_additional_rights', function (Blueprint $table){
            $table->id();
            $table->integer('user_id');
            $table->integer('entity_id');
            $table->boolean('full_access')->default(0);
            $table->string('entity_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('l_business_trip_additional_rights');
    }
};
