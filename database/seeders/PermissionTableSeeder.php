<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'category-list',
            'category-create',
            'category-edit',
            'category-delete',
            'client-list',
            'client-create',
            'client-edit',
            'client-delete',
            'admin-list',
            'admin-create',
            'admin-edit',
            'admin-delete',
            'designation-list',
            'designation-create',
            'designation-edit',
            'designation-delete',
            'member-list',
            'member-create',
            'member-edit',
            'member-delete',
            'project-list',
            'project-create',
            'project-edit',
            'project-delete',
            'feature-list',
            'feature-create',
            'feature-edit',
            'feature-delete',
            'task-list',
            'task-create',
            'task-edit',
            'task-delete',
            
         ];
       
         foreach ($permissions as $permission) {
              Permission::create(['name' => $permission]);
         }
    }
}
