<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use OpenAdmin\Admin\Auth\Database\Administrator;
use OpenAdmin\Admin\Auth\Database\Role;
use OpenAdmin\Admin\Auth\Database\Permission;
use OpenAdmin\Admin\Auth\Database\Menu;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates OpenAdmin roles and users based on roles defined in HomeController
     *
     * @return void
     */
    public function run(): void
    {
        // Define roles based on HomeController.php
        $roles = [
            [
                'name' => 'Account Manager',
                'slug' => 'account_manager',
            ],
            [
                'name' => 'Support Segmen',
                'slug' => 'support_segmen',
            ],
            [
                'name' => 'Regional Partner Invoicing',
                'slug' => 'regional_partner_invoicing',
            ],
            [
                'name' => 'Management Segmen',
                'slug' => 'management_segmen',
            ],
            [
                'name' => 'BMBS Regional',
                'slug' => 'bmbs_regional',
            ],
            [
                'name' => 'Service Operation',
                'slug' => 'service_operation',
            ],
            [
                'name' => 'SO Regional',
                'slug' => 'SO_regional',
            ],
            [
                'name' => 'Management Regional',
                'slug' => 'management_regional',
            ],
            [
                'name' => 'Project Management',
                'slug' => 'project_mgmt',
            ],
            [
                'name' => 'Solution Engineer',
                'slug' => 'solution_engineer',
            ],
            [
                'name' => 'Management Witel',
                'slug' => 'management_witel',
            ],
            [
                'name' => 'Support NJKI',
                'slug' => 'support_njki',
            ],
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
            ],
        ];

        // Create a wildcard permission that allows all routes
        // This is needed because check_route_permission is enabled in config
        $allPermission = Permission::firstOrCreate(
            ['slug' => 'all'],
            [
                'name' => 'All permission',
                'http_method' => '',
                'http_path' => '*',
            ]
        );
        $this->command->info("Created/Found permission: All permission (all)");

        // Create roles
        $createdRoles = [];
        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                ['name' => $roleData['name']]
            );
            $createdRoles[$roleData['slug']] = $role;

            // Assign the all permission to each role
            $role->permissions()->syncWithoutDetaching([$allPermission->id]);

            $this->command->info("Created/Found role: {$roleData['name']} ({$roleData['slug']})");
        }

        // Define admin users with their roles
        $users = [
            [
                'username' => 'hyundo',
                'password' => 'hyundo', // Change this password in production!
                'name' => 'Hyundo',
                'roles' => ['administrator'],
            ],
            [
                'username' => 'account_manager',
                'password' => 'password',
                'name' => 'Account Manager',
                'roles' => ['account_manager'],
            ],
            [
                'username' => 'regional_partner_invoicing',
                'password' => 'password',
                'name' => 'Regional Partner Invoicing User',
                'roles' => ['regional_partner_invoicing'],
            ],
            [
                'username' => 'support_segmen',
                'password' => 'password',
                'name' => 'Support Segmen User',
                'roles' => ['support_segmen'],
            ],
            [
                'username' => 'management_segmen',
                'password' => 'password',
                'name' => 'Management Segmen User',
                'roles' => ['management_segmen'],
            ],
            [
                'username' => 'bmbs_regional',
                'password' => 'password',
                'name' => 'BMBS Regional User',
                'roles' => ['bmbs_regional'],
            ],
            [
                'username' => 'service_operation',
                'password' => 'password',
                'name' => 'Service Operation User',
                'roles' => ['service_operation'],
            ],
            [
                'username' => 'so_regional',
                'password' => 'password',
                'name' => 'SO Regional User',
                'roles' => ['SO_regional'],
            ],
            [
                'username' => 'management_regional',
                'password' => 'password',
                'name' => 'Management Regional User',
                'roles' => ['management_regional'],
            ],
            [
                'username' => 'project_mgmt',
                'password' => 'password',
                'name' => 'Project Management User',
                'roles' => ['project_mgmt'],
            ],
            [
                'username' => 'solution_engineer',
                'password' => 'password',
                'name' => 'Solution Engineer User',
                'roles' => ['solution_engineer'],
            ],
            [
                'username' => 'management_witel',
                'password' => 'password',
                'name' => 'Management Witel User',
                'roles' => ['management_witel'],
            ],
            [
                'username' => 'support_njki',
                'password' => 'password',
                'name' => 'Support NJKI User',
                'roles' => ['support_njki'],
            ],
        ];

        // Create admin users and assign roles
        foreach ($users as $userData) {
            $user = Administrator::firstOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            // Assign roles to user
            $roleIds = [];
            foreach ($userData['roles'] as $roleSlug) {
                if (isset($createdRoles[$roleSlug])) {
                    $roleIds[] = $createdRoles[$roleSlug]->id;
                }
            }

            if (!empty($roleIds)) {
                $user->roles()->sync($roleIds);
                $this->command->info("Created/Updated user: {$userData['username']} with roles: " . implode(', ', $userData['roles']));
            }
        }

        // Create menu items
        $this->createMenus($createdRoles);

        $this->command->info('OpenAdmin users and roles seeded successfully!');
        $this->command->warn('IMPORTANT: Please change default passwords in production environment!');
    }

    /**
     * Create menu items for OpenAdmin sidebar
     *
     * @param array $roles
     * @return void
     */
    protected function createMenus(array $roles): void
    {
        // Clear existing menus (optional - comment out if you want to keep existing menus)
        // Menu::truncate();

        // Create Dashboard menu
        $dashboardMenu = Menu::firstOrCreate(
            ['uri' => '/'],
            [
                'parent_id' => 0,
                'order' => 1,
                'title' => 'Dashboard',
                'icon' => 'icon-chart-bar',
            ]
        );

        // Create Admin parent menu
        $adminMenu = Menu::firstOrCreate(
            ['title' => 'Admin', 'uri' => ''],
            [
                'parent_id' => 0,
                'order' => 2,
                'icon' => 'icon-server',
            ]
        );

        // Create Admin submenus
        $usersMenu = Menu::firstOrCreate(
            ['uri' => 'auth/users'],
            [
                'parent_id' => $adminMenu->id,
                'order' => 3,
                'title' => 'Users',
                'icon' => 'icon-users',
            ]
        );

        $rolesMenu = Menu::firstOrCreate(
            ['uri' => 'auth/roles'],
            [
                'parent_id' => $adminMenu->id,
                'order' => 4,
                'title' => 'Roles',
                'icon' => 'icon-user',
            ]
        );

        $permissionsMenu = Menu::firstOrCreate(
            ['uri' => 'auth/permissions'],
            [
                'parent_id' => $adminMenu->id,
                'order' => 5,
                'title' => 'Permissions',
                'icon' => 'icon-ban',
            ]
        );

        $menuMenu = Menu::firstOrCreate(
            ['uri' => 'auth/menu'],
            [
                'parent_id' => $adminMenu->id,
                'order' => 6,
                'title' => 'Menu',
                'icon' => 'icon-bars',
            ]
        );

        $logsMenu = Menu::firstOrCreate(
            ['uri' => 'auth/logs'],
            [
                'parent_id' => $adminMenu->id,
                'order' => 7,
                'title' => 'Operation log',
                'icon' => 'icon-history',
            ]
        );

        // Assign all menus to administrator role (and optionally all roles)
        if (isset($roles['administrator'])) {
            $adminRole = $roles['administrator'];

            // Assign all menus to administrator role
            $menuIds = [
                $dashboardMenu->id,
                $adminMenu->id,
                $usersMenu->id,
                $rolesMenu->id,
                $permissionsMenu->id,
                $menuMenu->id,
                $logsMenu->id,
            ];

            $adminRole->menus()->sync($menuIds);
            $this->command->info('Menu items created and assigned to administrator role');
        }
    }
}
