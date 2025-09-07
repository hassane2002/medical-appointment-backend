<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixDoctorAvailabilitiesConstraint extends Migration
{
    public function up()
    {
        // Supprimer la contrainte défectueuse et la recréer correctement
        Schema::table('doctor_availabilities', function (Blueprint $table) {
            // Vous pouvez supprimer l'ancienne contrainte si elle existe
            $table->dropColumn('day_of_week >= 0 AND day_of_week <= 6'); // Cette ligne peut échouer, c'est normal
        });
        
        // Ajouter une contrainte simple
        DB::statement('ALTER TABLE doctor_availabilities ADD CONSTRAINT check_day_of_week CHECK (day_of_week >= 0 AND day_of_week <= 6)');
    }

    public function down()
    {
        DB::statement('ALTER TABLE doctor_availabilities DROP CONSTRAINT check_day_of_week');
    }
}