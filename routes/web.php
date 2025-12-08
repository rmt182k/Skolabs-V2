<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AIGradingController;
use App\Http\Controllers\AISettingController;
use App\Http\Controllers\AITestController;
use App\Http\Controllers\AssessmentReportController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationalLevelController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskSubmissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/welcome', function () {
        return view('welcome');
    });

    // ==========================================
    // DASHBOARD
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ==========================================
    // PERMISSION MANAGEMENT API
    // ==========================================
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/api/menus', [MenuController::class, 'fetchMenus']);
    Route::get('/api/menu-users', [MenuController::class, 'fetchUserMenus']);
    Route::get('/api/menus/{id}/access-details', [MenuController::class, 'getAccessDetails']);
    Route::post('/api/menus', [MenuController::class, 'store']);
    Route::put('/api/menus/{id}', [MenuController::class, 'update']);
    Route::delete('/api/menus/{id}', [MenuController::class, 'destroy']);
    Route::post('/api/menus/update-order', [MenuController::class, 'updateOrder']);
    Route::post('/api/menus/{menuId}/role-permissions', [MenuController::class, 'updateRolePermissions']);
    Route::post('/api/menus/{menuId}/user-menu-overrides', [MenuController::class, 'updateUserOverride']);
    Route::delete('/api/menus/{menuId}/user-menu-overrides/{userId}', [MenuController::class, 'deleteUserOverride']);
    Route::post('/api/menus/{menuId}/user-permission-overrides', [MenuController::class, 'updateUserPermissionOverride']);
    Route::delete('/api/menus/{menuId}/user-permission-overrides/{overrideId}', [MenuController::class, 'deleteUserPermissionOverride']);

    // ==========================================
    // USER MANAGEMENT
    // ==========================================
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/api/users', [UserController::class, 'fetchUsers']);
    Route::get('/api/users/search', [UserController::class, 'searchUsers']);
    Route::get('/api/users/{userId}', [UserController::class, 'show']);
    Route::get('/api/roles/{roleId}/users', [UserController::class, 'getUsersByRole']);
    Route::post('/api/users', [UserController::class, 'store']);
    Route::put('/api/users/{userId}', [UserController::class, 'update']);
    Route::delete('/api/users/{userId}', [UserController::class, 'destroy']);
    Route::post('/api/users/{userId}/assign-roles', [UserController::class, 'assignRoles'])->name('api.users.assignRoles');
    Route::put('/api/users/{userId}/update-status', [UserController::class, 'updateStatus'])->name('api.users.updateStatus');

    // ==========================================
    // ROLE MANAGEMENT API
    // ==========================================
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/api/roles', [RoleController::class, 'fetchRoles']);
    Route::get('/api/roles', [RoleController::class, 'fetchRoles']);
    Route::get('/api/roles/{id}', [RoleController::class, 'show']);
    Route::post('/api/roles', [RoleController::class, 'store']);
    Route::post('/api/roles', [RoleController::class, 'store']);
    Route::put('/api/roles/{id}', [RoleController::class, 'update']);
    Route::put('/api/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/api/roles/{id}', [RoleController::class, 'destroy']);
    Route::delete('/api/roles/{id}', [RoleController::class, 'destroy']);

    // ==========================================
    // PERMISSION MANAGEMENT API
    // ==========================================
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/api/permissions', [PermissionController::class, 'fetchPermissions']);
    Route::get('/api/permissions/{userId}', [PermissionController::class, 'fetchUserPermissions']);
    Route::get('/api/permissions/{id}', [PermissionController::class, 'show']);
    Route::post('/api/permissions', [PermissionController::class, 'store']);
    Route::post('/api/permissions', [PermissionController::class, 'store']);
    Route::put('/api/permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('/api/permissions/{id}', [PermissionController::class, 'destroy']);

    // =========================================
    // EDUCATIONAL LEVELS API
    // =========================================
    Route::get('/educational-levels', [EducationalLevelController::class, 'index']);
    Route::get('/api/educational-levels', [EducationalLevelController::class, 'fetchAll']);
    Route::post('/api/educational-levels', [EducationalLevelController::class, 'store']);
    Route::get('/api/educational-levels/{id}', [EducationalLevelController::class, 'show']);
    Route::put('/api/educational-levels/{id}', [EducationalLevelController::class, 'update']);
    Route::delete('/api/educational-levels/{id}', [EducationalLevelController::class, 'destroy']);

    // =========================================
    // SUBJECTS API
    // =========================================
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/api/subjects', [SubjectController::class, 'fetchAll']);
    Route::get('/api/subjects/search', [SubjectController::class, 'search']);
    Route::post('/api/subjects', [SubjectController::class, 'store']);
    Route::get('/api/subjects/{id}', [SubjectController::class, 'show']);
    Route::put('/api/subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('/api/subjects/{id}', [SubjectController::class, 'destroy']);
    Route::get('/api/subjects-assignments', [SubjectController::class, 'fetchAllAssignments']);
    Route::get('/api/subjects-assignments/{teacherId}', [SubjectController::class, 'fetchTeacherAssignments']);
    Route::post('/api/subjects-assignments', [SubjectController::class, 'storeAssignment']);
    Route::delete('/api/subjects-assignments/{id}', [SubjectController::class, 'destroyAssignment']);
    Route::get('/api/subjects/assigned/search', [SubjectController::class, 'searchAssignedSubjects']);
    Route::get('/api/subjects/{subject_id}/teachers/search', [SubjectController::class, 'searchTeachersForSubject']);

    // =========================================
    // ACADEMIC YEARS API
    // =========================================
    Route::get('/academic-years', [AcademicYearController::class, 'index']);
    Route::get('/api/academic-years', [AcademicYearController::class, 'fetchAll']);
    Route::post('/api/academic-years', [AcademicYearController::class, 'store']);
    Route::get('/api/academic-years/{id}', [AcademicYearController::class, 'show']);
    Route::put('/api/academic-years/{id}', [AcademicYearController::class, 'update']);
    Route::delete('/api/academic-years/{id}', [AcademicYearController::class, 'destroy']);

    // =========================================
    // CLASSES API
    // =========================================
    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/api/classes', [ClassController::class, 'fetchAll']);
    Route::get('/api/classes/fetchUserClasses/{userId}', [ClassController::class, 'fetchUserClasses']);
    Route::post('/api/classes', [ClassController::class, 'store']);
    Route::get('/api/classes/{id}', [ClassController::class, 'show']);
    Route::put('/api/classes/{id}', [ClassController::class, 'update']);
    Route::delete('/api/classes/{id}', [ClassController::class, 'destroy']);

    // =========================================
    // CLASS MANAGEMENT
    // =========================================
    Route::get('/manage-classes/{classId}', [ManageClassController::class, 'index']);
    Route::get('/api/classes/{classId}/students', [ManageClassController::class, 'getStudentsInClass']);
    Route::post('/api/classes/{classId}/assign-students', [ManageClassController::class, 'assignStudents']);
    Route::delete('/api/classes/{classId}/remove-student/{userId}', [ManageClassController::class, 'removeStudent']);

    Route::get('/majors', [MajorController::class, 'index']);
    Route::get('/api/majors', [MajorController::class, 'fetchAll']);
    Route::post('/api/majors', [MajorController::class, 'store']);
    Route::get('/api/majors/{id}', [MajorController::class, 'show']);
    Route::put('/api/majors/{id}', [MajorController::class, 'update']);
    Route::delete('/api/majors/{id}', [MajorController::class, 'destroy']);

    // SCHEDULE
    Route::get('/api/classes/{classId}/schedule', [ScheduleController::class, 'getSchedule']);
    Route::post('/api/classes/{classId}/schedule/store', [ScheduleController::class, 'storeScheduleEntry']);
    Route::delete('/api/classes/{classId}/schedule/{schedule_id}/destroy', [ScheduleController::class, 'destroyScheduleEntry']);

    // LEARNING MATERIALS
    Route::get('/api/classes/{classId}/materials', [LearningMaterialController::class, 'getMaterials']);
    Route::post('/api/classes/{classId}/materials/store', [LearningMaterialController::class, 'storeMaterial']);
    Route::delete('/api/classes/{classId}/materials/{materialId}/destroy', [LearningMaterialController::class, 'destroyMaterial']);

    // Tasks
    Route::get('/classes/{classId}/tasks/create', [TaskController::class, 'create']);
    Route::get('/classes/{classId}/tasks/{taskId}/edit', [TaskController::class, 'edit']);
    Route::get('/api/classes/{classId}/tasks', [TaskController::class, 'getTasks']);
    Route::get('/api/classes/{classId}/tasks/{taskId}/details', [TaskController::class, 'getTaskDetails']);
    Route::post('/api/classes/{classId}/tasks/store', [TaskController::class, 'store']);
    Route::put('/api/classes/{classId}/tasks/{taskId}/update', [TaskController::class, 'update']);
    Route::delete('/api/classes/{classId}/tasks/{taskId}/delete', [TaskController::class, 'destroyTask']);

    Route::get('/classes/{classId}/tasks/{taskId}/submissions', [TaskSubmissionController::class, 'index']);
    Route::get('/api/classes/{classId}/tasks/{taskId}/submissions-data', [TaskSubmissionController::class, 'getSubmissionsData']);
    Route::get('/classes/{classId}/tasks/{taskId}/answer', [TaskSubmissionController::class, 'showAnswerSheet']);
    Route::get('/api/classes/{classId}/tasks/{taskId}/student-view', [TaskController::class, 'getTaskForStudent']);
    Route::post('/api/classes/{classId}/tasks/{taskId}/submit', [TaskSubmissionController::class, 'storeSubmission']);

    Route::get('/classes/{classId}/tasks/{task_id}/submissions/{submission_id}/grade', [TaskSubmissionController::class, 'showGradeForm']);
    Route::get('/api/submissions/{submission_id}/details', [TaskSubmissionController::class, 'getSubmissionDetails']);
    Route::post('/api/submissions/{submission_id}/grade', [TaskSubmissionController::class, 'saveGrades']);

    Route::get('/competencies', [CompetencyController::class, 'index'])->name('competencies.index');
    Route::get('/api/competencies/search', [CompetencyController::class, 'search'])->name('competencies.search');
    Route::get('/api/competencies', [CompetencyController::class, 'getCompetencies'])->name('competencies.list');
    Route::post('/api/competencies', [CompetencyController::class, 'store'])->name('competencies.store');
    Route::get('/api/competencies/{id}', [CompetencyController::class, 'show'])->name('competencies.show');
    Route::post('/api/competencies/{id}', [CompetencyController::class, 'update'])->name('competencies.update');
    Route::delete('/api/competencies/{id}', [CompetencyController::class, 'destroy'])->name('competencies.destroy');

    Route::get('/ai-settings', [AISettingController::class, 'index'])->name('index');
    Route::get('/api/ai-settings', [AISettingController::class, 'getSettingsApi'])->name('api.get');
    Route::post('/api/ai-settings/bulk', [AISettingController::class, 'bulkUpdate'])->name('api.bulk');
    Route::post('/api/ai-settings/store', [AISettingController::class, 'store'])->name('api.store');
    Route::delete('/api/ai-settings/{taskKey}', [AISettingController::class, 'destroy'])->name('api.destroy');

    //AI JOBS
    Route::post('/api/submissions/{submission_id}/run-ai', [AIGradingController::class, 'runAIAnalysis']);
    Route::get('/api/submissions/{submission_id}/report', [AIGradingController::class, 'getStudentReport']);
    Route::get('/api/ai-providers', [AIGradingController::class, 'getAvailableProviders']);
    Route::get('/api/answers/{answer_id}/ai-raw-results', [AIGradingController::class, 'getAIRawResults']);
    Route::get('/api/ai-statistics', [AIGradingController::class, 'getAIStatistics']);
    Route::post('/api/submissions/{submission_id}/answers/{answer_id}/retry-ai', [AIGradingController::class, 'retrySingleAnswerAnalysis']);

    // REPORT
    Route::get('/submissions/{submission_id}/report', [ReportController::class, 'index']);
    Route::get('/api/submissions/{submission_id}/report', [AssessmentReportController::class, 'getStudentReport']);

    // TEST AI
    Route::get('/api/ai-test', [AITestController::class, 'testAIConnections']);
});
