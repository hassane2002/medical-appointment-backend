<?php
// database/migrations/2025_08_27_163620_create_appointment_slots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentSlotsTable extends Migration
{
    public function up()
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            // Clé étrangère
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            
            // Index pour optimiser les requêtes
            $table->index(['doctor_id', 'slot_date', 'is_booked']);
            $table->index(['slot_date', 'start_time']);
            
            // Contrainte d'unicité pour éviter les créneaux en double
            $table->unique(['doctor_id', 'slot_date', 'start_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_slots');
    }
}