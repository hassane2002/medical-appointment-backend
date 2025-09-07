<?php

// database/seeders/SpecialtySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialty;

class SpecialtySeeder extends Seeder
{
    public function run()
    {
        $specialties = [
            [
                'name' => 'Médecine Générale',
                'description' => 'Consultation de médecine générale pour tous types de pathologies courantes',
                'consultation_price' => 25000
            ],
            [
                'name' => 'Cardiologie',
                'description' => 'Spécialiste du cœur et des maladies cardiovasculaires',
                'consultation_price' => 35000
            ],
            [
                'name' => 'Dermatologie',
                'description' => 'Spécialiste de la peau et des maladies cutanées',
                'consultation_price' => 30000
            ],
            [
                'name' => 'Pédiatrie',
                'description' => 'Médecine spécialisée dans la santé des enfants et adolescents',
                'consultation_price' => 28000
            ],
            [
                'name' => 'Gynécologie',
                'description' => 'Santé féminine, grossesse et accouchement',
                'consultation_price' => 32000
            ],
            [
                'name' => 'Ophtalmologie',
                'description' => 'Spécialiste des yeux et de la vision',
                'consultation_price' => 30000
            ],
            [
                'name' => 'ORL',
                'description' => 'Spécialiste des oreilles, nez, gorge',
                'consultation_price' => 30000
            ],
            [
                'name' => 'Neurologie',
                'description' => 'Spécialiste du système nerveux et du cerveau',
                'consultation_price' => 40000
            ],
            [
                'name' => 'Psychiatrie',
                'description' => 'Spécialiste de la santé mentale et des troubles psychologiques',
                'consultation_price' => 35000
            ],
            [
                'name' => 'Orthopédie',
                'description' => 'Spécialiste des os, articulations et système musculaire',
                'consultation_price' => 35000
            ],
            [
                'name' => 'Gastro-entérologie',
                'description' => 'Spécialiste du système digestif',
                'consultation_price' => 33000
            ],
            [
                'name' => 'Pneumologie',
                'description' => 'Spécialiste des poumons et des voies respiratoires',
                'consultation_price' => 32000
            ]
        ];

        foreach ($specialties as $specialty) {
            Specialty::firstOrCreate(['name' => $specialty['name']], $specialty);
        }

        $this->command->info('Spécialités créées avec succès');
    }
}
