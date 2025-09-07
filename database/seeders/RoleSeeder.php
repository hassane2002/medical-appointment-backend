<?php

// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'patient',
                'description' => 'Utilisateur patient qui peut prendre des rendez-vous'
            ],
            [
                'name' => 'doctor',
                'description' => 'Médecin praticien qui peut gérer ses consultations'
            ],
            [
                'name' => 'admin',
                'description' => 'Administrateur système avec tous les privilèges'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->info('Rôles créés avec succès');
    }
}