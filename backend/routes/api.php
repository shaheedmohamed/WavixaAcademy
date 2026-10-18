<?php

use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminCertificateController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEnrollmentController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminQuizController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminVideoImportController;
use App\Http\Controllers\Admin\AdminInstructorController;
use App\Http\Controllers\Admin\AdminUploadController;
use App\Http\Controllers\Instructor\InstructorCourseController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Instructor\InstructorQuizAttemptController;
use App\Http\Controllers\Instructor\InstructorQuizController;
use App\Http\Controllers\Instructor\InstructorStudentController;
use App\Http\Controllers\Instructor\InstructorVideoImportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\DeviceController;
use App\Http\Controllers\Student\EventController as StudentEventController;
use App\Http\Controllers\Student\LearningController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\ProjectController;
use App\Http\Controllers\Student\QuizController;
use App\Http\Controllers\Student\VideoController;
use App\Http\Controllers\Webhook\MuxWebhookController;
use Illuminate\Support\Facades\Route;

// ---------------- Public ----------------
Route::get('/settings/public', [PublicController::class, 'publicSettings']);
Route::get('/courses', [PublicController::class, 'courses']);
Route::get('/courses/{course:slug}', [PublicController::class, 'course']);
Route::get('/courses/{course:slug}/lessons/{lesson}', [PublicController::class, 'lessonPreview']);
Route::get('/events', [PublicController::class, 'events']);
Route::get('/events/{event:slug}', [PublicController::class, 'event']);
Route::get('/instructors', [PublicController::class, 'instructors']);
Route::get('/faqs', [PublicController::class, 'faqs']);
Route::get('/certificates/verify/{token}', [PublicController::class, 'verifyCertificate']);
Route::post('/contact', [PublicController::class, 'contact'])->middleware('throttle:5,1');

// Local video streaming — signature + live-session check, no cookie auth
// (the player fetches segments directly). Generous throttle for bursts.
Route::get('/video/stream/{asset}/{session}/{sig}/{file}', [StreamController::class, 'file'])
    ->middleware('throttle:600,1');

// ---------------- Auth ----------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/email/verify/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1');
    });
});

// ---------------- Authenticated student ----------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/dashboard', [LearningController::class, 'dashboard']);
    Route::get('/me/courses', [LearningController::class, 'courses']);
    Route::get('/me/courses/{course:slug}', [LearningController::class, 'course']);
    Route::get('/me/courses/{course:slug}/lessons/{lesson}', [LearningController::class, 'lesson']);
    Route::post('/me/progress', [LearningController::class, 'recordProgress']);

    // Video playback (CourseShield)
    Route::post('/video/session', [VideoController::class, 'start'])->middleware('throttle:30,1');
    Route::post('/video/heartbeat', [VideoController::class, 'heartbeat'])->middleware('throttle:300,1');
    Route::post('/video/session/end', [VideoController::class, 'end'])->middleware('throttle:60,1');

    // Events
    Route::post('/events/{event}/register', [StudentEventController::class, 'register']);
    Route::delete('/events/{event}/register', [StudentEventController::class, 'cancel']);
    Route::get('/me/events', [StudentEventController::class, 'myEvents']);
    Route::get('/me/events/{registration}/ticket', [StudentEventController::class, 'ticket']);

    // Projects & quizzes
    Route::get('/me/courses/{course:slug}/projects', [ProjectController::class, 'index']);
    Route::get('/me/projects/pending', [ProjectController::class, 'pending']);
    Route::post('/projects/{project}/submit', [ProjectController::class, 'submit']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::post('/quizzes/{quiz}/attempts', [QuizController::class, 'attempt'])->middleware('throttle:10,1');
    Route::get('/me/quiz-attempts', [QuizController::class, 'myAttempts']);

    // Uploads
    Route::post('/uploads', [AdminUploadController::class, 'store']);

    // Certificates & notifications & devices
    Route::get('/me/certificates', [CertificateController::class, 'index']);
    Route::get('/me/notifications', [NotificationController::class, 'index']);
    Route::post('/me/notifications/read', [NotificationController::class, 'markRead']);
    Route::get('/me/devices', [DeviceController::class, 'index']);
    Route::delete('/me/devices/{device}', [DeviceController::class, 'destroy']);
    Route::put('/me/profile', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
});

// ---------------- Admin ----------------
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/stats', [AdminDashboardController::class, 'stats']);

    // Students
    Route::get('/students', [AdminStudentController::class, 'index']);
    Route::get('/students/{user}', [AdminStudentController::class, 'show']);
    Route::post('/students/{user}/enrollments', [AdminStudentController::class, 'activateEnrollment']);
    Route::patch('/students/{user}/status', [AdminStudentController::class, 'updateStatus']);
    Route::post('/students/{user}/sessions/terminate', [AdminStudentController::class, 'terminateSessions']);
    Route::delete('/students/{user}/devices/{device}', [AdminStudentController::class, 'removeDevice']);
    Route::delete('/students/{user}', [AdminStudentController::class, 'destroy']);

    // Instructors
    Route::apiResource('instructors', AdminInstructorController::class);

    // Courses / modules / lessons
    Route::apiResource('courses', AdminCourseController::class);
    Route::post('/courses/{course}/modules', [AdminCourseController::class, 'storeModule']);
    Route::post('/courses/{course}/cohorts', [AdminCourseController::class, 'storeCohort']);
    Route::get('/instructors/dropdown', [AdminInstructorController::class, 'dropdown']);
    Route::patch('/modules/{module}', [AdminCourseController::class, 'updateModule']);
    Route::delete('/modules/{module}', [AdminCourseController::class, 'destroyModule']);
    Route::post('/modules/{module}/lessons', [AdminCourseController::class, 'storeLesson']);
    Route::patch('/lessons/{lesson}', [AdminCourseController::class, 'updateLesson']);
    Route::delete('/lessons/{lesson}', [AdminCourseController::class, 'destroyLesson']);

    // Video imports
    Route::get('/video-imports', [AdminVideoImportController::class, 'index']);
    Route::post('/lessons/{lesson}/import-video', [AdminVideoImportController::class, 'import']);
    Route::post('/lessons/{lesson}/upload-video', [AdminVideoImportController::class, 'upload']);
    Route::get('/video-imports/{asset}', [AdminVideoImportController::class, 'show']);
    Route::post('/video-imports/{asset}/retry', [AdminVideoImportController::class, 'retry']);

    // Uploads (images)
    Route::post('/uploads', [AdminUploadController::class, 'store']);

    // Enrollments
    Route::get('/enrollments', [AdminEnrollmentController::class, 'index']);
    Route::patch('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'update']);
    Route::delete('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'destroy']);

    // Projects & submissions
    Route::get('/projects', [AdminProjectController::class, 'projects']);
    Route::post('/courses/{course}/projects', [AdminProjectController::class, 'storeProject']);
    Route::patch('/projects/{project}', [AdminProjectController::class, 'updateProject']);
    Route::delete('/projects/{project}', [AdminProjectController::class, 'destroyProject']);
    Route::get('/submissions', [AdminProjectController::class, 'submissions']);
    Route::get('/submissions/{submission}/file', [AdminProjectController::class, 'download']);
    Route::patch('/submissions/{submission}', [AdminProjectController::class, 'review']);

    // Quizzes
    Route::get('/quizzes', [AdminQuizController::class, 'index']);
    Route::post('/courses/{course}/quizzes', [AdminQuizController::class, 'store']);
    Route::get('/quizzes/{quiz}', [AdminQuizController::class, 'show']);
    Route::patch('/quizzes/{quiz}', [AdminQuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy']);
    Route::get('/quizzes/{quiz}/attempts', [AdminQuizController::class, 'attempts']);

    // Events
    Route::apiResource('events', AdminEventController::class);
    Route::get('/events/{event}/registrations', [AdminEventController::class, 'registrations']);
    Route::post('/events/checkin', [AdminEventController::class, 'checkin']);

    // Certificates
    Route::get('/certificates', [AdminCertificateController::class, 'index']);
    Route::post('/certificates', [AdminCertificateController::class, 'issue']);
    Route::delete('/certificates/{certificate}', [AdminCertificateController::class, 'destroy']);

    // Announcements
    Route::apiResource('announcements', AdminAnnouncementController::class);

    // FAQs
    Route::apiResource('faqs', AdminFaqController::class);

    // Security center
    Route::get('/security/sessions', [AdminSecurityController::class, 'sessions']);
    Route::get('/security/devices', [AdminSecurityController::class, 'devices']);
    Route::get('/security/events', [AdminSecurityController::class, 'events']);
    Route::post('/security/events/{event}/resolve', [AdminSecurityController::class, 'resolve']);
    Route::post('/security/sessions/{session}/terminate', [AdminSecurityController::class, 'terminateSession']);
    Route::delete('/security/devices/{device}', [AdminSecurityController::class, 'removeDevice']);

    // Audit log
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);
});

// ---------------- Instructor ----------------
Route::prefix('instructor')->middleware(['auth:sanctum', 'instructor'])->group(function () {
    Route::get('/stats', [InstructorDashboardController::class, 'stats']);

    Route::get('/courses', [InstructorCourseController::class, 'index']);
    Route::post('/courses', [InstructorCourseController::class, 'store']);
    Route::get('/courses/{course}', [InstructorCourseController::class, 'show']);
    Route::patch('/courses/{course}', [InstructorCourseController::class, 'update']);
    Route::delete('/courses/{course}', [InstructorCourseController::class, 'destroy']);
    Route::post('/courses/{course}/modules', [InstructorCourseController::class, 'storeModule']);
    Route::post('/courses/{course}/lessons', [InstructorCourseController::class, 'storeDirectLesson']);
    Route::post('/courses/{course}/cohorts', [InstructorCourseController::class, 'storeCohort']);
    Route::patch('/modules/{module}', [InstructorCourseController::class, 'updateModule']);
    Route::delete('/modules/{module}', [InstructorCourseController::class, 'destroyModule']);
    Route::post('/modules/{module}/lessons', [InstructorCourseController::class, 'storeLesson']);
    Route::patch('/lessons/{lesson}', [InstructorCourseController::class, 'updateLesson']);
    Route::delete('/lessons/{lesson}', [InstructorCourseController::class, 'destroyLesson']);
    Route::post('/lessons/{lesson}/upload-video', [InstructorVideoImportController::class, 'upload']);
    Route::post('/lessons/{lesson}/import-video', [InstructorVideoImportController::class, 'import']);
    Route::post('/uploads', [AdminUploadController::class, 'store']);

    // Students & enrollments
    Route::get('/students', [InstructorStudentController::class, 'index']);
    Route::get('/users/search', [InstructorStudentController::class, 'search']);
    Route::post('/users/{user}/enrollments', [InstructorStudentController::class, 'activateEnrollment']);
    Route::patch('/enrollments/{enrollment}', [InstructorStudentController::class, 'updateEnrollment']);

    // Quizzes
    Route::get('/quizzes', [InstructorQuizController::class, 'index']);
    Route::post('/courses/{course}/quizzes', [InstructorQuizController::class, 'store']);
    Route::get('/quizzes/{quiz}', [InstructorQuizController::class, 'show']);
    Route::patch('/quizzes/{quiz}', [InstructorQuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [InstructorQuizController::class, 'destroy']);
    Route::get('/quizzes/{quiz}/attempts', [InstructorQuizController::class, 'attempts']);
    Route::get('/quiz-attempts', [InstructorQuizAttemptController::class, 'index']);
    Route::patch('/quiz-attempts/{attempt}/review', [InstructorQuizAttemptController::class, 'review']);
    Route::delete('/quiz-attempts/{attempt}', [InstructorQuizAttemptController::class, 'destroy']);
});

// ---------------- Webhooks ----------------
Route::post('/webhooks/mux', [MuxWebhookController::class, 'handle']);
