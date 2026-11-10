<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentDocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (landing page, login, sign-up, forgot password)
|--------------------------------------------------------------------------
*/
Route::view('/', 'landing')->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.store');

Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->name('password.otp.send');
Route::get('/verify-otp', [PasswordResetController::class, 'showVerify'])->name('password.otp.form');
Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
Route::get('/resend-otp', [PasswordResetController::class, 'resendOtp'])->name('password.otp.resend');
Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

/*
|--------------------------------------------------------------------------
| Any logged-in user
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('password.change.form');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.change');
    Route::get('/users/{userId}/photo', [ProfileController::class, 'photo'])->name('users.photo');

    Route::middleware('role:ADMIN,STUDENT')->group(function () {
        Route::get('/packages/{packageId}/subjects', [PaymentDocumentController::class, 'packageSubjects'])->name('packages.subjects')->whereNumber('packageId');
        Route::get('/receipts/{id}', [PaymentDocumentController::class, 'receipt'])->name('receipts.show');
        Route::get('/payments/{paymentId}/proof', [PaymentDocumentController::class, 'proof'])->name('payments.proof');
    });
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('superadmin')->group(function () {
        Route::get('/admins', [Admin\AdminController::class, 'index'])->name('admins.index');
        Route::get('/admins/create', [Admin\AdminController::class, 'create'])->name('admins.create');
        Route::post('/admins', [Admin\AdminController::class, 'store'])->name('admins.store');
        Route::delete('/admins/{userId}', [Admin\AdminController::class, 'destroy'])->name('admins.destroy');
        Route::patch('/admins/{userId}/role', [Admin\AdminController::class, 'updateRole'])->name('admins.role');
    });

    Route::get('/teachers', [Admin\TeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/create', [Admin\TeacherController::class, 'create'])->name('teachers.create');
    Route::post('/teachers', [Admin\TeacherController::class, 'store'])->name('teachers.store');
    Route::put('/teachers/{userId}', [Admin\TeacherController::class, 'update'])->name('teachers.update');
    Route::delete('/teachers/{userId}', [Admin\TeacherController::class, 'destroy'])->name('teachers.destroy');

    Route::get('/students', [Admin\StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [Admin\StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [Admin\StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{userId}', [Admin\StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{userId}', [Admin\StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/registrations/{subRegId}/drop', [Admin\StudentController::class, 'dropRegistration'])->name('registrations.drop');

    Route::get('/subjects', [Admin\SubjectController::class, 'index'])->name('subjects.index');
    Route::get('/subjects/create', [Admin\SubjectController::class, 'create'])->name('subjects.create');
    Route::post('/subjects', [Admin\SubjectController::class, 'store'])->name('subjects.store');
    Route::put('/subjects/{subjectId}', [Admin\SubjectController::class, 'update'])->name('subjects.update')->whereNumber('subjectId');
    Route::delete('/subjects/{subjectId}', [Admin\SubjectController::class, 'destroy'])->name('subjects.destroy')->whereNumber('subjectId');

    Route::get('/packages/create', [Admin\PackageController::class, 'create'])->name('packages.create');
    Route::post('/packages', [Admin\PackageController::class, 'store'])->name('packages.store');
    Route::put('/packages/{packageId}', [Admin\PackageController::class, 'update'])->name('packages.update')->whereNumber('packageId');
    Route::delete('/packages/{packageId}', [Admin\PackageController::class, 'destroy'])->name('packages.destroy')->whereNumber('packageId');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/process', [Admin\PaymentController::class, 'process'])->name('payments.process');
    Route::post('/payments/generate-bills', [Admin\PaymentController::class, 'generateBills'])->name('payments.generate');
});

/*
|--------------------------------------------------------------------------
| Teacher
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:TEACHER'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/students', [TeacherController::class, 'students'])->name('students');
    Route::get('/subjects', [TeacherController::class, 'subjects'])->name('subjects');
    Route::get('/subjects/{subjectId}/students', [TeacherController::class, 'subjectStudents'])->name('subjects.students')->whereNumber('subjectId');
});

/*
|--------------------------------------------------------------------------
| Student
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:STUDENT'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/subjects', [Student\StudentController::class, 'subjects'])->name('subjects.index');
    Route::post('/register', [Student\StudentController::class, 'register'])->name('register');
    Route::post('/registrations/{subRegId}/drop', [Student\StudentController::class, 'drop'])->name('registrations.drop');

    Route::get('/payments', [Student\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/checkout', [Student\PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('/payments/upload', [Student\PaymentController::class, 'upload'])->name('payments.upload');
});
