<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->after('id');
            $table->string('first_name', 100)->after('role_id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->text('address')->nullable()->after('date_of_birth');
            $table->string('city', 100)->nullable()->after('address');
            $table->boolean('is_active')->default(true)->after('city');
            
            // Ajouter la clé étrangère
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'role_id', 'first_name', 'last_name', 'phone', 
                'date_of_birth', 'address', 'city', 'is_active'
            ]);
        });
    }
};
