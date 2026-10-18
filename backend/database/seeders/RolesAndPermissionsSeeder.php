<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'courses.view', 'courses.create', 'courses.update', 'courses.delete',
            'modules.manage', 'lessons.manage', 'video.import',
            'students.view', 'students.manage', 'enrollments.manage',
            'projects.review', 'quizzes.manage',
            'events.manage', 'events.checkin',
            'certificates.issue',
            'announcements.manage',
            'security.view', 'security.manage',
            'audit.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->givePermissionTo($permissions);

        Role::findOrCreate('content_manager', 'web')->givePermissionTo([
            'courses.view', 'courses.create', 'courses.update',
            'modules.manage', 'lessons.manage', 'video.import',
            'quizzes.manage', 'events.manage', 'announcements.manage',
        ]);

        Role::findOrCreate('instructor', 'web')->givePermissionTo([
            'courses.view', 'lessons.manage', 'projects.review',
            'events.manage', 'events.checkin',
        ]);

        Role::findOrCreate('support', 'web')->givePermissionTo([
            'courses.view', 'students.view', 'enrollments.manage',
            'events.checkin', 'security.view',
        ]);

        Role::findOrCreate('student', 'web');
    }
}
