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
        Schema::create('doctor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->integer('day_of_week'); // 0=Dimanche, 1=Lundi, ..., 6=Samedi
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // Clé étrangère
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            
            // Contrainte : day_of_week doit être entre 0 et 6
            $table->integer('day_of_week >= 0 AND day_of_week <= 6');
           
            // Index pour optimiser les requêtes
            $table->index(['doctor_id', 'day_of_week', 'is_available']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctor_availabilities');
    }
};
