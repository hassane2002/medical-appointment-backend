<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Doctor;
use App\Models\Specialty;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Récupérer les rôles
        $patientRole = Role::where('name', 'patient')->first();
        $doctorRole = Role::where('name', 'doctor')->first();
        $adminRole = Role::where('name', 'admin')->first();

        // Créer un administrateur
        $admin = User::firstOrCreate([
            'email' => 'admin@medical-app.com'
        ], [
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'System',
           
            'email' => 'admin@medical-app.com',
            'password' => Hash::make('admin123'),
            'phone' => '77 000 00 00',
            'city' => 'Dakar',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Créer des patients de test
        $patients = [
            [
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont@example.com',
                'phone' => '77 123 45 67',
                'date_of_birth' => '1990-05-15',
                'address' => '123 Rue de la Paix, Dakar',
                'city' => 'Dakar'
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Diop',
                'email' => 'marie.diop@example.com',
                'phone' => '77 234 56 78',
                'date_of_birth' => '1985-08-22',
                'address' => '456 Avenue Cheikh Anta Diop, Dakar',
                'city' => 'Dakar'
            ]
        ];

        foreach ($patients as $patientData) {
            User::firstOrCreate([
                'email' => $patientData['email']
            ], array_merge($patientData, [
                'role_id' => $patientRole->id,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]));
        }

        // Créer des médecins de test
        $doctors = [
            [
                'user' => [
                    'first_name' => 'Dr. Amadou',
                    'last_name' => 'FALL',
                    'email' => 'dr.amadou.fall@example.com',
                    'phone' => '77 987 65 43',
                    'address' => 'Cabinet Médical des Almadies, Dakar',
                    'city' => 'Dakar'
                ],
                'doctor' => [
                    'specialty' => 'Médecine Générale',
                    'license_number' => 'MED001',
                    'years_of_experience' => 15,
                    'consultation_fee' => 25000,
                    'cabinet_address' => 'Cabinet Médical des Almadies, Route des Almadies, Dakar',
                    'cabinet_phone' => '33 860 12 34',
                    'bio' => 'Médecin généraliste avec 15 ans d\'expérience dans la prise en charge globale du patient.',
                    'is_verified' => true
                ]
            ],
            [
                'user' => [
                    'first_name' => 'Dr. Fatou',
                    'last_name' => 'SARR',
                    'email' => 'dr.fatou.sarr@example.com',
                    'phone' => '77 876 54 32',
                    'address' => 'Clinique Cardiologique de Dakar',
                    'city' => 'Dakar'
                ],
                'doctor' => [
                    'specialty' => 'Cardiologie',
                    'license_number' => 'CARD001',
                    'years_of_experience' => 12,
                    'consultation_fee' => 35000,
                    'cabinet_address' => 'Clinique Cardiologique, Avenue Bourguiba, Dakar',
                    'cabinet_phone' => '33 821 45 67',
                    'bio' => 'Cardiologue spécialisée dans les pathologies cardiovasculaires et la prévention.',
                    'is_verified' => true
                ]
            ],
            [
                'user' => [
                    'first_name' => 'Dr. Ousmane',
                    'last_name' => 'BA',
                    'email' => 'dr.ousmane.ba@example.com',
                    'phone' => '77 765 43 21',
                    'address' => 'Centre de Dermatologie du Point E',
                    'city' => 'Dakar'
                ],
                'doctor' => [
                    'specialty' => 'Dermatologie',
                    'license_number' => 'DERM001',
                    'years_of_experience' => 8,
                    'consultation_fee' => 30000,
                    'cabinet_address' => 'Centre de Dermatologie, Point E, Dakar',
                    'cabinet_phone' => '33 842 33 44',
                    'bio' => 'Dermatologue spécialisé dans les affections cutanées et esthétiques.',
                    'is_verified' => false // En attente de vérification
                ]
            ]
        ];

        foreach ($doctors as $doctorData) {
            // Créer l'utilisateur médecin
            $user = User::firstOrCreate([
                'email' => $doctorData['user']['email']
            ], array_merge($doctorData['user'], [
                'role_id' => $doctorRole->id,
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]));
        
            // Trouver la spécialité
            $specialty = Specialty::where('name', $doctorData['doctor']['specialty'])->first();
        
            // Préparer les données du médecin sans la clé 'specialty'
            $doctorArray = $doctorData['doctor'];
            unset($doctorArray['specialty']); // <-- très important !
        
            // Ajouter l'id de la spécialité
            $doctorArray['specialty_id'] = $specialty->id;
        
            // Créer le profil médecin
            Doctor::firstOrCreate([
                'user_id' => $user->id
            ], $doctorArray);
        }
        

        $this->command->info('Utilisateurs de test créés avec succès');
        $this->command->info('Admin: admin@medical-app.com / admin123');
        $this->command->info('Patient: jean.dupont@example.com / password123');
        $this->command->info('Docteur: dr.amadou.fall@example.com / password123');
    }
}
