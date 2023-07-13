<?php

use App\Modules\BusinessTrip\Model\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLBusinessTripRole extends Migration {
    public function up(){
        Schema::create('l_business_trip_role', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('note')->nullable();

            $table->timestamps();
        });

        $roles = ['admin' => 'Администратор'];
        foreach($roles as $name => $note){
            $newRole        = new Role();
            $newRole->name  = $name;
            $newRole->note  = $note;
            $newRole->save();
            if($name == 'admin')
                $newRole->users()->sync([14287,14317,12467]);
        }

    }

    public function down(){
        Schema::dropIfExists('l_business_trip_role');
    }
};
