<?php

use App\Http\Controllers\Api\V1\AcademicStatusController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BuildingController;
use App\Http\Controllers\Api\V1\ClassRoomController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\CurriculumController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\FacultyController;
use App\Http\Controllers\Api\V1\LecturerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SemesterController;
use App\Http\Controllers\Api\V1\StudyProgramController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'SIMORA API is running',
            'service' => 'Laravel',
        ]);
    });

    // Auth Publik — rate limit 5 menit untuk mencegah brute force.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Profil sendiri — auth cukup (tidak perlu permission).
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);

        // Manajemen pengguna — API_SPECIFICATION §3.2.
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.read');
        Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.read');
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:users.read');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:users.read');

        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');

        Route::middleware('permission:users.update')->group(function () {
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
            Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);
            Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
            Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);
        });

        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

        // ------------------------------------------------------------------
        // Master data (API_SPECIFICATION §3.3) — pola CRUD seragam dengan
        // permission `<resource>.<action>` per endpoint.
        // ------------------------------------------------------------------
        Route::group(['prefix' => 'faculties'], function () {
            Route::get('/', [FacultyController::class, 'index'])->middleware('permission:faculties.read');
            Route::get('/{faculty}', [FacultyController::class, 'show'])->middleware('permission:faculties.read');
            Route::post('/', [FacultyController::class, 'store'])->middleware('permission:faculties.create');
            Route::patch('/{faculty}', [FacultyController::class, 'update'])->middleware('permission:faculties.update');
            Route::delete('/{faculty}', [FacultyController::class, 'destroy'])->middleware('permission:faculties.delete');
        });

        Route::group(['prefix' => 'departments'], function () {
            Route::get('/', [DepartmentController::class, 'index'])->middleware('permission:departments.read');
            Route::get('/{department}', [DepartmentController::class, 'show'])->middleware('permission:departments.read');
            Route::post('/', [DepartmentController::class, 'store'])->middleware('permission:departments.create');
            Route::patch('/{department}', [DepartmentController::class, 'update'])->middleware('permission:departments.update');
            Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:departments.delete');
        });

        Route::group(['prefix' => 'study-programs'], function () {
            Route::get('/', [StudyProgramController::class, 'index'])->middleware('permission:study_programs.read');
            Route::get('/{study_program}', [StudyProgramController::class, 'show'])->middleware('permission:study_programs.read');
            Route::post('/', [StudyProgramController::class, 'store'])->middleware('permission:study_programs.create');
            Route::patch('/{study_program}', [StudyProgramController::class, 'update'])->middleware('permission:study_programs.update');
            Route::delete('/{study_program}', [StudyProgramController::class, 'destroy'])->middleware('permission:study_programs.delete');
        });

        Route::group(['prefix' => 'academic-years'], function () {
            Route::get('/', [AcademicYearController::class, 'index'])->middleware('permission:academic_years.read');
            Route::get('/{academic_year}', [AcademicYearController::class, 'show'])->middleware('permission:academic_years.read');
            Route::post('/', [AcademicYearController::class, 'store'])->middleware('permission:academic_years.create');
            Route::patch('/{academic_year}', [AcademicYearController::class, 'update'])->middleware('permission:academic_years.update');
            Route::delete('/{academic_year}', [AcademicYearController::class, 'destroy'])->middleware('permission:academic_years.delete');
        });

        Route::group(['prefix' => 'semesters'], function () {
            Route::get('/', [SemesterController::class, 'index'])->middleware('permission:semesters.read');
            Route::get('/{semester}', [SemesterController::class, 'show'])->middleware('permission:semesters.read');
            Route::post('/', [SemesterController::class, 'store'])->middleware('permission:semesters.create');
            Route::post('/{semester}/activate', [SemesterController::class, 'activate'])->middleware('permission:semesters.update');
            Route::patch('/{semester}', [SemesterController::class, 'update'])->middleware('permission:semesters.update');
            Route::delete('/{semester}', [SemesterController::class, 'destroy'])->middleware('permission:semesters.delete');
        });

        Route::group(['prefix' => 'academic-statuses'], function () {
            Route::get('/', [AcademicStatusController::class, 'index'])->middleware('permission:academic_statuses.read');
            Route::get('/{academic_status}', [AcademicStatusController::class, 'show'])->middleware('permission:academic_statuses.read');
            Route::post('/', [AcademicStatusController::class, 'store'])->middleware('permission:academic_statuses.create');
            Route::patch('/{academic_status}', [AcademicStatusController::class, 'update'])->middleware('permission:academic_statuses.update');
            Route::delete('/{academic_status}', [AcademicStatusController::class, 'destroy'])->middleware('permission:academic_statuses.delete');
        });

        Route::group(['prefix' => 'buildings'], function () {
            Route::get('/', [BuildingController::class, 'index'])->middleware('permission:buildings.read');
            Route::get('/{building}', [BuildingController::class, 'show'])->middleware('permission:buildings.read');
            Route::post('/', [BuildingController::class, 'store'])->middleware('permission:buildings.create');
            Route::patch('/{building}', [BuildingController::class, 'update'])->middleware('permission:buildings.update');
            Route::delete('/{building}', [BuildingController::class, 'destroy'])->middleware('permission:buildings.delete');
        });

        Route::group(['prefix' => 'class-rooms'], function () {
            Route::get('/', [ClassRoomController::class, 'index'])->middleware('permission:class_rooms.read');
            Route::get('/{class_room}', [ClassRoomController::class, 'show'])->middleware('permission:class_rooms.read');
            Route::post('/', [ClassRoomController::class, 'store'])->middleware('permission:class_rooms.create');
            Route::patch('/{class_room}', [ClassRoomController::class, 'update'])->middleware('permission:class_rooms.update');
            Route::delete('/{class_room}', [ClassRoomController::class, 'destroy'])->middleware('permission:class_rooms.delete');
        });

        Route::group(['prefix' => 'courses'], function () {
            Route::get('/', [CourseController::class, 'index'])->middleware('permission:courses.read');
            Route::get('/{course}', [CourseController::class, 'show'])->middleware('permission:courses.read');
            Route::post('/', [CourseController::class, 'store'])->middleware('permission:courses.create');
            Route::patch('/{course}', [CourseController::class, 'update'])->middleware('permission:courses.update');
            Route::delete('/{course}', [CourseController::class, 'destroy'])->middleware('permission:courses.delete');
        });

        Route::group(['prefix' => 'curriculums'], function () {
            Route::get('/', [CurriculumController::class, 'index'])->middleware('permission:curriculums.read');
            Route::get('/{curriculum}', [CurriculumController::class, 'show'])->middleware('permission:curriculums.read');
            Route::post('/', [CurriculumController::class, 'store'])->middleware('permission:curriculums.create');
            Route::patch('/{curriculum}', [CurriculumController::class, 'update'])->middleware('permission:curriculums.update');

            Route::middleware('permission:curriculums.update')->group(function () {
                Route::post('/{curriculum}/courses', [CurriculumController::class, 'attachCourse']);
                Route::delete('/{curriculum}/courses/{courseId}', [CurriculumController::class, 'detachCourse'])->whereNumber('courseId');
                Route::post('/{curriculum}/prerequisites', [CurriculumController::class, 'attachPrerequisite']);
                Route::delete('/{curriculum}/prerequisites/{prerequisiteCourseId}', [CurriculumController::class, 'detachPrerequisite'])->whereNumber('prerequisiteCourseId');
            });

            Route::delete('/{curriculum}', [CurriculumController::class, 'destroy'])->middleware('permission:curriculums.delete');
        });

        Route::group(['prefix' => 'lecturers'], function () {
            Route::get('/', [LecturerController::class, 'index'])->middleware('permission:lecturers.read');
            Route::get('/{lecturer}', [LecturerController::class, 'show'])->middleware('permission:lecturers.read');
            Route::post('/', [LecturerController::class, 'store'])->middleware('permission:lecturers.create');
            Route::patch('/{lecturer}', [LecturerController::class, 'update'])->middleware('permission:lecturers.update');
            Route::delete('/{lecturer}', [LecturerController::class, 'destroy'])->middleware('permission:lecturers.delete');
        });

        Route::group(['prefix' => 'students'], function () {
            Route::get('/', [StudentController::class, 'index'])->middleware('permission:students.read');
            Route::get('/{student}', [StudentController::class, 'show'])->middleware('permission:students.read');
            Route::post('/', [StudentController::class, 'store'])->middleware('permission:students.create');
            Route::patch('/{student}', [StudentController::class, 'update'])->middleware('permission:students.update');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete');
        });
    });
});
