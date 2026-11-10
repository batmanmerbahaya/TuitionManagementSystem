<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectRegistration;
use App\Support\Locks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Student dashboard, the Subject page and the registration logic (register / drop).
 */
class StudentController extends Controller
{
    private function student(): Student
    {
        return Student::where('userid', Auth::id())->firstOrFail();
    }

    public function dashboard()
    {
        $student = $this->student();

        return view('student.dashboard', [
            'subCount' => Student::registeredSubjectCount($student->studentid),
            'payCount' => Student::actionablePaymentCount($student->studentid),
            'timetableGrid' => Student::timetableGrid($student->studentid),
            'packages' => Package::availableForStudent($student->studentlevel, $student->studentid),
            'fullPackageIds' => Package::fullPackageIds($student->studentlevel),
            'studentLevel' => $student->studentlevel,
        ]);
    }

    public function subjects()
    {
        $student = $this->student();
        $registeredItems = SubjectRegistration::registeredItems($student->studentid);

        // A registration can only be self-dropped while it is still UNPAID. Once a proof is uploaded
        // (PENDING), approved, or even rejected, only an admin may drop it.
        $statuses = Payment::latestStatuses($registeredItems->pluck('subregid')->unique()->values()->all());
        $dropEligibility = $registeredItems->pluck('subregid')->unique()
            ->mapWithKeys(fn ($id) => [$id => strcasecmp($statuses[$id] ?? 'UNPAID', 'UNPAID') === 0])
            ->all();

        return view('student.subjects', [
            'registeredItems' => $registeredItems,
            'rowCounts' => $registeredItems->countBy('subregid')->all(),
            'dropEligibility' => $dropEligibility,
            'availPackages' => Package::availableForStudent($student->studentlevel, $student->studentid),
            'availSubjects' => Subject::availableForStudent($student->studentlevel, $student->studentid),
            // Packages containing a full subject are listed but flagged as unavailable.
            'fullPackageIds' => Package::fullPackageIds($student->studentlevel),
        ]);
    }

    /**
     * AJAX: register a package (type=package&id=..) or individual subjects (type=subject&subjectIds[]=..).
     * Replies with plain text "success" or an error message.
     */
    public function register(Request $request)
    {
        $student = Student::where('userid', Auth::id())->first();
        if (! $student) {
            return response('error');
        }

        try {
            // Everything below runs one registration at a time, so two students cannot both
            // take the last seat and two requests cannot generate the same IDs.
            return response(Locks::run(function () use ($request, $student) {
                $isFirstTime = SubjectRegistration::isFirstTime($student->studentid);

                if ($request->input('type') === 'package') {
                    $package = Package::where('packageid', (int) $request->input('id'))
                        ->where('packagelevel', $student->studentlevel)
                        ->first();
                    if (! $package) {
                        return 'error';
                    }
                    if (SubjectRegistration::where('studentid', $student->studentid)->where('packageid', $package->packageid)
                        ->where('subregstatusflag', '<>', 'D')->exists()) {
                        return 'You are already registered for this package.';
                    }

                    // Blocked if any subject inside the package is already full.
                    if ($full = Package::fullSubjectName($package->packageid)) {
                        return "This package is unavailable because \"$full\" is already full.";
                    }

                    $clash = Subject::studentScheduleClash($student->studentid, $package->subjects()->pluck('subjects.subjectid')->all());
                    if ($clash) {
                        return 'Timetable clash: '.$clash;
                    }

                    $registration = SubjectRegistration::registerPackage($student->studentid, $package->packageid);
                    Payment::bill($registration->subregid, (float) $package->packagefee, 'TUITION');
                    if ($isFirstTime) {
                        Payment::bill($registration->subregid, Payment::registrationFee(), 'REGISTRATION');
                    }

                    return 'success';
                }

                $subjectIds = array_unique(array_map('intval', (array) $request->input('subjectIds', [])));
                $subjects = Subject::whereIn('subjectid', $subjectIds)->where('subjectlevel', $student->studentlevel)->get();
                if ($subjects->isEmpty()) {
                    return 'error';
                }

                // Subjects the student is not taking yet (directly or inside a package).
                $stillAvailable = Subject::availableForStudent($student->studentlevel, $student->studentid)->pluck('subjectid')->map(fn ($id) => (int) $id);
                foreach ($subjects as $subject) {
                    if (! $stillAvailable->contains((int) $subject->subjectid)) {
                        return "You are already taking \"{$subject->subjectname}\".";
                    }
                }

                // Reject the whole batch if any selected subject is already full.
                foreach ($subjects as $subject) {
                    if (Subject::isFull($subject->subjectid)) {
                        return "\"{$subject->subjectname}\" is already full.";
                    }
                }

                $clash = Subject::studentScheduleClash($student->studentid, $subjects->pluck('subjectid')->all());
                if ($clash) {
                    return 'Timetable clash: '.$clash;
                }

                $firstRegistration = null;
                foreach ($subjects as $subject) {
                    $registration = SubjectRegistration::registerSubject($student->studentid, $subject->subjectid);
                    $firstRegistration ??= $registration;
                    Payment::bill($registration->subregid, (float) $subject->subjectfee, 'TUITION');
                }
                if ($isFirstTime && $firstRegistration) {
                    Payment::bill($firstRegistration->subregid, Payment::registrationFee(), 'REGISTRATION');
                }

                return 'success';
            }));
        } catch (\Throwable $e) {
            Log::error('Registration failed: '.$e->getMessage());

            return response('error');
        }
    }

    /** The student drops one of their own registrations (only while it is UNPAID). */
    public function drop(string $subRegId)
    {
        $student = $this->student();

        if (! SubjectRegistration::ownedBy($subRegId, $student->studentid)) {
            return redirect()->route('student.subjects.index', ['error' => 'dropfailed']);
        }

        $status = Payment::latestStatus($subRegId);

        if (strcasecmp($status, 'UNPAID') === 0) {
            $isPackage = SubjectRegistration::whereKey($subRegId)->whereNotNull('packageid')->exists();
            DB::transaction(function () use ($subRegId) {
                Payment::resolveForDrop($subRegId);
                SubjectRegistration::drop($subRegId);
            });

            return redirect()->route('student.subjects.index', ['status' => 'dropped', 'type' => $isPackage ? 'package' : 'subject']);
        }

        return redirect()->route('student.subjects.index', [
            'error' => strcasecmp($status, 'PENDING') === 0 ? 'pendingVerify' : 'subscriptionActive',
        ]);
    }
}
