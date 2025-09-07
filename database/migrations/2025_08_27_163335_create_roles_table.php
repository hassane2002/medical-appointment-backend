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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insertion des rôles par défaut
        DB::table('roles')->insert([
            ['name' => 'patient', 'description' => 'Utilisateur patient', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'doctor', 'description' => 'Médecin praticien', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'description' => 'Administrateur système', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
