<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder permission Phase 2 — konvensi `<resource>.<action>`.
 * Menanam KOMPLET permission (untuk dipakai fase berikutnya) lalu
 * menyinkronkan ke role sesuai matriks RBAC PROJECT_PLAN §4. Idempotent.
 */
class PermissionsSeeder extends Seeder
{
    /** Resource yang punya CRUD penuh (read/create/update/delete). */
    public const RESOURCES = [
        'users', 'faculties', 'departments', 'study_programs', 'academic_years',
        'semesters', 'academic_statuses', 'buildings', 'class_rooms', 'lecturers',
        'students', 'courses', 'curriculums', 'course_classes', 'schedules',
        'study_plans', 'attendances', 'grade_components', 'grades',
        'tuition_bills', 'bill_types', 'payments', 'announcements', 'reports',
    ];

    public const ACTIONS = ['read', 'create', 'update', 'delete'];

    /**
     * Peta role → daftar permission (mengikuti PROJECT_PLAN §4).
     * '*' menandakan semua permission.
     *
     * @return array<string, string|list<string>>
     */
    public static function roleMatrix(): array
    {
        $master = ['faculties', 'departments', 'study_programs', 'academic_years', 'semesters',
            'academic_statuses', 'buildings', 'class_rooms', 'lecturers', 'students',
            'courses', 'curriculums', 'course_classes', 'schedules'];

        return [
            'super_admin' => '*',

            'admin_akademik' => array_merge(
                ['users.read', 'users.create', 'users.update'],
                self::permissionsFor($master, ['read', 'create', 'update', 'delete']),
                ['study_plans.read', 'study_plans.update', 'attendances.read', 'reports.read'],
                self::permissionsFor(['announcements'], ['read', 'create', 'update', 'delete'])
            ),

            'dosen' => array_merge(
                ['students.read', 'courses.read', 'curriculums.read', 'schedules.read'],
                self::permissionsFor(['attendances'], ['read', 'create', 'update']),
                self::permissionsFor(['grade_components'], ['read', 'create', 'update', 'delete']),
                self::permissionsFor(['grades'], ['read', 'create', 'update']),
                ['study_plans.read', 'study_plans.update']
            ),

            'mahasiswa' => [
                'study_plans.read', 'study_plans.create', 'study_plans.update',
                'grades.read', 'tuition_bills.read', 'payments.create',
            ],

            'keuangan' => array_merge(
                ['students.read', 'reports.read'],
                self::permissionsFor(['tuition_bills', 'bill_types'], ['read', 'create', 'update', 'delete']),
                self::permissionsFor(['payments'], ['read', 'update'])
            ),

            // Pimpinan: read-only segala hal.
            'pimpinan' => array_merge(
                self::permissionsFor(
                    array_merge($master, ['study_plans', 'attendances', 'grades', 'payments', 'announcements']),
                    ['read']
                ),
                ['reports.read', 'grade_components.read', 'bill_types.read']
            ),
        ];
    }

    public function run(): void
    {
        // 1) Tanam semua permission (idempotent — findOrCreate).
        $allPermissions = [];
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $permission = Permission::findOrCreate("{$resource}.{$action}", 'web');
                $allPermissions[] = $permission->name;
            }
        }

        // 2) Sinkronkan matrix ke tiap role.
        foreach (self::roleMatrix() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($permissions === '*') {
                $role->syncPermissions($allPermissions);

                continue;
            }

            // Saring hanya permission yang memang sudah ada di tabel.
            $role->syncPermissions(
                Permission::query()->whereIn('name', $permissions)->pluck('name')->all()
            );
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return list<string> kombinasi `<resource>.<action>` */
    public static function permissionsFor(array $resources, array $actions): array
    {
        $names = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $names[] = "{$resource}.{$action}";
            }
        }

        return $names;
    }
}
