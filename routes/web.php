<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamGroupController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard/Index');
// })->middleware(['auth', 'verified'])->name('dashboard');


//route homepage
Route::get('/', function () {
    //return view login
    return Inertia::render('Student/Login/Index');
});

//login students
Route::post('/student/login', \App\Http\Controllers\LoginController::class)->name('student.login');
Route::post('/student/logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('student.logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/apps/profile', [ProfileController::class, 'edit'])->name('apps.profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
    // dashboard route
    Route::get('/', DashboardController::class)->name('dashboard');
    // permissions route
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    // roles route
    Route::resource('/roles', RoleController::class)->except(['create', 'edit', 'show']);
    // users route
    Route::resource('/users', UserController::class)->except('show');


    // Features
    Route::resource('classrooms', ClassroomController::class);
    Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::resource('students', StudentController::class);
    Route::resource('teachers', TeacherController::class)->except(['create', 'edit', 'show']);
    Route::resource('lessons', LessonController::class);
    Route::resource('exams', ExamController::class);
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    //custom route for create question exam
    Route::get('/exams/{exam}/questions/create', [QuestionController::class, 'create'])->name('exams.questions.create');
    Route::get('/exams/{exam}/questions/template', [QuestionController::class, 'downloadTemplate'])->name('exams.questions.template');
    Route::post('/exams/{exam}/questions/import', [QuestionController::class, 'import'])->name('exams.questions.import');
    Route::post('/questions/editor-image', [QuestionController::class, 'uploadEditorImage'])->name('questions.editor-image');
    //custom route for store question exam
    Route::post('/exams/{exam}/questions/store', [QuestionController::class, 'store'])->name('exams.questions.store');
    //custom route for edit question exam
    Route::get('/exams/{exam}/questions/{question}/edit', [QuestionController::class, 'edit'])->name('exams.questions.edit');
    //custom route for update question exam
    Route::put('/exams/{exam}/questions/{question}/update', [QuestionController::class, 'update'])->name('exams.questions.update');
    //custom route for destroy question exam
    Route::delete('/exams/{exam}/questions/{question}/destroy', [QuestionController::class, 'destroy'])->name('exams.questions.destroy');

    //route resource exam_sessions
    Route::resource('/exam_sessions', ExamSessionController::class);

    //custom route for enrolle create
    Route::get('/exam_sessions/{exam_session}/group/create', [ExamGroupController::class, 'create'])->name('exam_sessions.group.create');
    //custom route for enrolle store
    Route::post('/exam_sessions/{exam_session}/group/store', [ExamGroupController::class, 'store'])->name('exam_sessions.group.store');
    //custom route for enrolle destroy
    Route::delete('/exam_sessions/{exam_session}/group/{exam_group}/destroy', [ExamGroupController::class, 'destroy'])->name('exam_sessions.group.destroy');


    //route index reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    //route index reports filter
    Route::get('/reports/filter', [ReportController::class, 'filter'])->name('reports.filter');

    Route::get('/corrections', [CorrectionController::class, 'index'])->name('corrections.index');
    Route::put('/corrections/{answer}', [CorrectionController::class, 'update'])->name('corrections.update');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::patch('/monitoring/{examGroup}/unlock', [MonitoringController::class, 'unlock'])->name('monitoring.unlock');

});


//prefix "student"
Route::prefix('student')->group(function () {

    //middleware "student"
    Route::group(['middleware' => 'student'], function () {

        //route dashboard
        Route::get('/dashboard', [ExaminationController::class, 'index'])->name('student.dashboard');

        //route exam confirmation
        Route::get('/examination-confirmation/{id}', [ExaminationController::class, 'confirmation'])->name('student.examination.confirmation');

        //route exam start
        Route::get('/examination-start/{id}', [ExaminationController::class, 'startExam'])->name('student.examination.startExam');

        //catat perpindahan tab/aplikasi selama ujian
        Route::post('/examination-violation', [ExaminationController::class, 'reportViolation'])->name('student.examination.reportViolation');

        //route exam show
        Route::get('/examination/{id}/{page}', [ExaminationController::class, 'show'])->name('student.examination.show');

        //route exam update duration
        Route::put('/examination-duration/update/{grade_id}', [ExaminationController::class, 'updateDuration'])->name('student.examination.update_duration');

        //route answer question
        Route::post('/examination-answer', [ExaminationController::class, 'answerQuestion'])->name('student.examination.answerQuestion');

        //route exam end
        Route::post('/examination-end', [ExaminationController::class, 'endExam'])->name('student.examination.endExam');

        //route exam result
        Route::get('/examination-result/{exam_group_id}', [ExaminationController::class, 'resultExam'])->name('student.examination.resultExam');
    });

});

require __DIR__ . '/auth.php';
