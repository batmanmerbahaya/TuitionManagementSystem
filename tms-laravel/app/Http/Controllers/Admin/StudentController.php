<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\SubjectRegistration;
use App\Models\User;
use App\Support\Format;
use App\Support\Ids;
use App\Support\Locks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Student list: add, edit and delete students, and drop their registrations. */
class StudentController extends Controller
{
    public function index()
    {
        $students = Student::listing();

        // Enrolment rows for the "manage subjects" modal of every student, with each
        // registration's latest payment status - fetched in two queries for the whole page.
        $items = SubjectRegistration::registeredItems();
        $statuses = Payment::latestStatuses($items->pluck('subregid')->unique()->values()->all());

        $enrolments = $items->groupBy('studentid')->map(function ($rows) use ($statuses) {
            return $rows->groupBy('subregid')->map(function ($regRows, $subRegId) use ($statuses) {
                $first = $regRows->first();
                $isPackage = $first->packagename !== '-';
                $status = $statuses[$subRegId] ?? 'UNPAID';

                return (object) [
                    'subRegId' => $subRegId,
                    'isPackage' => $isPackage,
                    // A package is ONE registration covering several subjects, so it is one row with one drop button.
                    'label' => $isPackage
                        ? $first->packagename.' Package ('.$regRows->pluck('subjectname')->implode(', ').')'
                        : $first->subjectname,
                    'status' => $status,
                    'pillClass' => match (strtoupper($status)) {
                        'APPROVED', 'PAID' => 'status-approved',
                        'REJECTED' => 'status-rejected',
                        'UNPAID' => 'status-unpaid',
                        default => 'status-pending',
                    },
                ];
            })->values();
        });

        return view('admin.students.index', [
            'students' => $students,
            'enrolments' => $enrolments,
            'totalCount' => $students->count(),
            'countF4' => $students->filter(fn ($s) => str_contains((string) $s->studentlevel, '4'))->count(),
            'countF5' => $students->filter(fn ($s) => str_contains((string) $s->studentlevel, '5'))->count(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['type' => 'student']);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));
        $password = (string) $request->input('password');
        $level = in_array($request->input('level'), ['FORM 4', 'FORM 5'], true) ? $request->input('level') : 'FORM 4';

        if ($error = $this->accountError($name, $email, $phone, $password, true)) {
            return redirect()->route('admin.students.create', ['error' => $error]);
        }
        if (! $this->isValidOptionalImage($request, 'image')) {
            return redirect()->route('admin.students.create', ['error' => 'invalidImage']);
        }
        if (User::emailTaken($email)) {
            return redirect()->route('admin.students.create', ['error' => 'emailTaken']);
        }

        try {
            Locks::run(function () use ($request, $name, $email, $phone, $password, $level) {
                $user = User::createAccount($name, $email, $phone, $password, $this->fileBytes($request, 'image'));
                Student::create(['studentid' => Ids::nextStudent(), 'userid' => $user->userid, 'studentlevel' => $level]);
            });
        } catch (\Throwable $e) {
            Log::error('Add student failed: '.$e->getMessage());

            return redirect()->route('admin.students.create', ['status' => 'fail']);
        }

        return redirect()->route('admin.students.create', ['status' => 'success']);
    }

    /**
     * Admin edits a student's name, email, phone and level (e.g. moving Form 4 students up to Form 5).
     * The level can only change once the student has no active registrations, because their
     * current subjects/packages belong to the old level.
     */
    public function update(Request $request, string $userId)
    {
        $user = User::active()->with('student')->whereHas('student')->find($userId);
        $back = fn (string $msg) => redirect()->route('admin.students.index', ['error' => 'edit', 'msg' => $msg]);

        if (! $user) {
            return $back('Student not found.');
        }

        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Format::normalisePhone($request->input('phone'));
        $level = (string) $request->input('level');

        if ($error = $this->accountError($name, $email, $phone)) {
            return $back(TeacherController::errorText($error));
        }
        if (! in_array($level, ['FORM 4', 'FORM 5'], true)) {
            return $back('Please choose Form 4 or Form 5.');
        }
        if (User::emailTaken($email, $userId)) {
            return $back('That email is already used by another account.');
        }

        $student = $user->student;
        if ($level !== $student->studentlevel && SubjectRegistration::hasActive($student->studentid)) {
            return $back("{$user->username} is still registered for {$student->studentlevel} subjects. Drop those registrations (Manage Enrolled Subjects) before changing the level.");
        }

        DB::transaction(function () use ($user, $student, $name, $email, $phone, $level) {
            $user->update(['username' => $name, 'useremail' => $email, 'usercontact' => $phone]);
            $student->update(['studentlevel' => $level]);
        });

        return redirect()->route('admin.students.index', ['status' => 'success', 'msg' => "$name's details were updated."]);
    }

    public function destroy(string $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('admin.students.index', ['error' => 'true']);
        }

        // A student with any active registration (paid or not) cannot be deleted - drop it first.
        $student = Student::where('userid', $userId)->first();
        if ($student && SubjectRegistration::hasActive($student->studentid)) {
            return redirect()->route('admin.students.index', ['error' => 'hasRegisteredSubjects']);
        }

        $user->update(['statusflag' => 'D']);

        return redirect()->route('admin.students.index', ['status' => 'success', 'msg' => ($user->username ?: 'Student').' was removed.']);
    }

    /** Admin force-drops one subject/package registration of a student. */
    public function dropRegistration(string $subRegId)
    {
        if (! SubjectRegistration::whereKey($subRegId)->where('subregstatusflag', '<>', 'D')->exists()) {
            return redirect()->route('admin.students.index', ['error' => 'edit', 'msg' => 'That registration was not found or is already dropped.']);
        }

        DB::transaction(function () use ($subRegId) {
            Payment::resolveForDrop($subRegId);
            SubjectRegistration::drop($subRegId);
        });

        return redirect()->route('admin.students.index', ['status' => 'success', 'msg' => 'Subject dropped successfully.']);
    }
}
