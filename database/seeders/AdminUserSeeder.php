<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Créer l'administrateur
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@elpirata.fr',
            'password' => Hash::make('admin123'), 
            'email_verified_at' => now(),
        ]);

        // Récupérer le rôle admin
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            // Attribuer le rôle admin
            $admin->roles()->attach($adminRole);
        }

        $this->command->info('Administrateur créé avec succès !');
        $this->command->info('Email: admin@elpirata.fr');
        $this->command->info('Mot de passe: admin123');
        $this->command->warn('N\'oubliez pas de changer le mot de passe en production !');
    }
}
