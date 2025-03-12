<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Création des rôles de base
        $roles = [
            [
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Administrateur du système'
            ],
            [
                'name' => 'Modérateur',
                'slug' => 'moderator',
                'description' => 'Modérateur du contenu'
            ],
            [
                'name' => 'Utilisateur',
                'slug' => 'user',
                'description' => 'Utilisateur standard'
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Création des permissions de base
        $permissions = [
            // Permissions utilisateurs
            [
                'name' => 'Voir les utilisateurs',
                'slug' => 'users.view',
                'description' => 'Peut voir la liste des utilisateurs'
            ],
            [
                'name' => 'Créer des utilisateurs',
                'slug' => 'users.create',
                'description' => 'Peut créer de nouveaux utilisateurs'
            ],
            [
                'name' => 'Modifier des utilisateurs',
                'slug' => 'users.edit',
                'description' => 'Peut modifier les utilisateurs existants'
            ],
            [
                'name' => 'Supprimer des utilisateurs',
                'slug' => 'users.delete',
                'description' => 'Peut supprimer des utilisateurs'
            ],
            
            // Permissions des rôles
            [
                'name' => 'Gérer les rôles',
                'slug' => 'roles.manage',
                'description' => 'Peut gérer les rôles et permissions'
            ],
            
            // Permissions du contenu
            [
                'name' => 'Créer du contenu',
                'slug' => 'content.create',
                'description' => 'Peut créer du contenu'
            ],
            [
                'name' => 'Modifier du contenu',
                'slug' => 'content.edit',
                'description' => 'Peut modifier le contenu'
            ],
            [
                'name' => 'Supprimer du contenu',
                'slug' => 'content.delete',
                'description' => 'Peut supprimer du contenu'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Attribution des permissions aux rôles
        $adminRole = Role::where('slug', 'admin')->first();
        $moderatorRole = Role::where('slug', 'moderator')->first();
        $userRole = Role::where('slug', 'user')->first();

        // Admin a toutes les permissions
        $adminRole->permissions()->attach(Permission::all());

        // Modérateur a les permissions de contenu et de vue des utilisateurs
        $moderatorRole->permissions()->attach(
            Permission::whereIn('slug', [
                'content.create',
                'content.edit',
                'content.delete',
                'users.view'
            ])->get()
        );

        // Utilisateur standard a les permissions de base
        $userRole->permissions()->attach(
            Permission::whereIn('slug', [
                'content.create',
                'content.edit'
            ])->get()
        );
    }
}
