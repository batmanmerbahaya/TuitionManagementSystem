<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Format;
use App\Support\Ids;
use App\Support\Locks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Teacher list: add, edit and delete teachers. */
class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::listing();

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'totalTeachers' => $teachers->count(),
            'assignedCount' => $teachers->filter(fn ($t) => ! empty($t->subjects))->count(),
            'unassignedCount' => $teachers->filter(fn ($t) => empty($t->subjects))->count(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['type' => 'teacher']);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));
        $password = (string) $request->input('password');

        if ($error = $this->accountError($name, $email, $phone, $password, true)) {
            return redirect()->route('admin.teachers.create', ['error' => $error]);
        }
        if (! $this->isValidOptionalImage($request, 'image')) {
            return redirect()->route('admin.teachers.create', ['error' => 'invalidImage']);
        }
        if (User::emailTaken($email)) {
            return redirect()->route('admin.teachers.create', ['error' => 'emailTaken']);
        }

        try {
            Locks::run(function () use ($request, $name, $email, $phone, $password) {
                $user = User::createAccount($name, $email, $phone, $password, $this->fileBytes($request, 'image'));
                Teacher::create(['teacherid' => Ids::nextTeacher(), 'userid' => $user->userid]);
            });
        } catch (\Throwable $e) {
            Log::error('Add teacher failed: '.$e->getMessage());

            return redirect()->route('admin.teachers.create', ['status' => 'fail']);
        }

        return redirect()->route('admin.teachers.create', ['status' => 'success']);
    }

    /** Admin edits a teacher's name, email and phone. */
    public function update(Request $request, string $userId)
    {
        $user = User::active()->whereHas('teacher')->find($userId);
        $back = fn (string $msg) => redirect()->route('admin.teachers.index', ['status' => 'error', 'msg' => $msg]);

        if (! $user) {
            return $back('Teacher not found.');
        }

        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));

        if ($error = $this->accountError($name, $email, $phone)) {
            return $back(self::errorText($error));
        }
        if (User::emailTaken($email, $userId)) {
            return $back('That email is already used by another account.');
        }

        $user->update(['username' => $name, 'useremail' => $email, 'usercontact' => $phone]);

        return redirect()->route('admin.teachers.index', ['status' => 'updated', 'name' => $name]);
    }

    public function destroy(string $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('admin.teachers.index', ['status' => 'error', 'msg' => 'NoIdProvided']);
        }

        // A teacher can only be removed once they no longer teach any subject.
        if (Subject::whereIn('teacherid', Teacher::where('userid', $userId)->select('teacherid'))->exists()) {
            return redirect()->route('admin.teachers.index', ['status' => 'error', 'reason' => 'hasSubjects', 'name' => $user->username]);
        }

        $user->update(['statusflag' => 'D']);

        return redirect()->route('admin.teachers.index', ['status' => 'success', 'name' => $user->username ?: 'Teacher']);
    }

    public static function errorText(string $code): string
    {
        return match ($code) {
            'invalidName' => 'Name can only contain letters, spaces, apostrophes and @.',
            'invalidEmail' => 'Please enter a valid email address.',
            'invalidPhone' => 'Invalid phone number. Format: 12-345 6789.',
            'weakPassword' => 'Password must be at least 8 characters with an uppercase letter, a lowercase letter and a number.',
            default => 'Please check the details and try again.',
        };
    }
}
