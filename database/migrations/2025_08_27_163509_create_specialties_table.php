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
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('consultation_price', 10, 2)->default(0);
            $table->timestamps();
        });
        
        // Insertion des spécialités par défaut
        DB::table('specialties')->insert([
            ['name' => 'Médecine Générale', 'description' => 'Consultation de médecine générale', 'consultation_price' => 25000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cardiologie', 'description' => 'Spécialiste du cœur et des maladies cardiovasculaires', 'consultation_price' => 35000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dermatologie', 'description' => 'Spécialiste de la peau et des maladies cutanées', 'consultation_price' => 30000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pédiatrie', 'description' => 'Médecine des enfants', 'consultation_price' => 28000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gynécologie', 'description' => 'Santé féminine et obstétrique', 'consultation_price' => 32000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ophtalmologie', 'description' => 'Spécialiste des yeux', 'consultation_price' => 30000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ORL', 'description' => 'Oreilles, nez, gorge', 'consultation_price' => 30000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Neurologie', 'description' => 'Spécialiste du système nerveux', 'consultation_price' => 40000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('specialties');
    }
};
