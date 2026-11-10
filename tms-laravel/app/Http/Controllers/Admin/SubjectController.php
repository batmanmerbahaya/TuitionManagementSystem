<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\Format;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Subject list: add, update and delete subjects. */
class SubjectController extends Controller
{
    private const NAME_PATTERN = '/^[A-Za-z0-9\s]+$/';

    /** Subject & package management page (both tabs). */
    public function index()
    {
        $subjects = Subject::listingWithOccupancy()->map(fn ($s) => [
            'id' => (string) $s->subjectid,
            'code' => $s->subjectcode,
            'name' => $s->subjectname,
            'level' => $s->subjectlevel,
            'fee' => (string) $s->subjectfee,
            'day' => $s->subjectday,
            'start' => Format::hm($s->subtimestart),
            'end' => Format::hm($s->subtimeend),
            'avail' => (string) $s->subavailability,
            'registered' => (string) $s->registered_count,
            'teacherId' => (string) $s->teacherid,
        ]);

        $packages = Package::with('subjects:subjectid')->orderBy('packagelevel')->orderBy('packagename')->get()
            ->map(fn ($p) => [
                'id' => (string) $p->packageid,
                'code' => $p->packagecode,
                'name' => $p->packagename,
                'fee' => (string) $p->packagefee,
                'level' => $p->packagelevel,
                'subjectIds' => $p->subjects->pluck('subjectid')->implode(','),
            ]);

        $byLevel = fn ($items, string $level) => $items->filter(fn ($i) => strcasecmp((string) $i['level'], $level) === 0)->values();

        return view('admin.subjects.index', [
            'teachers' => Teacher::options()->map(fn ($t) => ['id' => $t->teacherid, 'name' => $t->username])->values(),
            'form4Subjects' => $byLevel($subjects, 'FORM 4'),
            'form5Subjects' => $byLevel($subjects, 'FORM 5'),
            'form4Packages' => $byLevel($packages, 'FORM 4'),
            'form5Packages' => $byLevel($packages, 'FORM 5'),
        ]);
    }

    public function create()
    {
        return view('admin.subjects.create', [
            'teachers' => Teacher::options(),
            'days' => Subject::DAYS,
        ]);
    }

    /** Adds one or more subjects submitted as parallel arrays (code[], name[], ...). */
    public function store(Request $request)
    {
        $codes = array_values((array) $request->input('code', []));
        $names = array_values((array) $request->input('name', []));
        $levels = array_values((array) $request->input('level', []));
        $fees = array_values((array) $request->input('fee', []));
        $days = array_values((array) $request->input('day', []));
        $starts = array_values((array) $request->input('start', []));
        $ends = array_values((array) $request->input('end', []));
        $avails = array_values((array) $request->input('avail', []));
        $teachers = array_values((array) $request->input('teacher', []));

        $back = fn (string $status, string $msg) => redirect()->route('admin.subjects.create', ['status' => $status, 'msg' => $msg]);

        $rows = [];
        foreach ($codes as $i => $rawCode) {
            $code = strtoupper(trim((string) $rawCode));
            if (! $this->isValidCode($code)) {
                return $back('invalidCode', "Subject code '$code' is invalid. Use the format XXXF4 or XXXF5 (e.g. BIOF4).");
            }

            $name = trim((string) ($names[$i] ?? ''));
            if (! preg_match(self::NAME_PATTERN, $name)) {
                return $back('invalidName', "Subject name '$name' is invalid. Use only letters, numbers and spaces (e.g. Biology Form 4).");
            }
            $name = Format::sentenceCase($name);

            $teacher = trim((string) ($teachers[$i] ?? ''));
            if ($teacher === '') {
                return $back('invalidCode', "Please assign a teacher to '$name'.");
            }

            $start = Format::hm($starts[$i] ?? '');
            $end = Format::hm($ends[$i] ?? '');
            if ($start === '' || $end === '' || $start >= $end) {
                return $back('conflict', "'$name' must end after it starts.");
            }
            if (! Subject::withinTimetableHours($start, $end)) {
                return $back('conflict', "'$name' must be between ".config('tms.day_start').' and '.config('tms.day_end').'.');
            }
            if ((float) ($fees[$i] ?? 0) < 0 || (int) ($avails[$i] ?? 0) < 1) {
                return $back('conflict', "'$name' needs a fee of RM 0 or more and at least 1 slot.");
            }

            $rows[] = [
                'subjectcode' => 'SUB-'.$code,
                'subjectname' => $name,
                'subjectlevel' => in_array($levels[$i] ?? '', ['FORM 4', 'FORM 5'], true) ? $levels[$i] : 'FORM 4',
                'subjectfee' => (float) ($fees[$i] ?? 0),
                'subjectday' => in_array($days[$i] ?? '', Subject::DAYS, true) ? $days[$i] : 'Monday',
                'subtimestart' => $start,
                'subtimeend' => $end,
                'subavailability' => (int) ($avails[$i] ?? 0),
                'teacherid' => $teacher,
            ];
        }

        if (empty($rows)) {
            return $back('invalidCode', 'Please add at least one subject.');
        }

        // The new rows must not overlap each other for the same teacher ...
        foreach ($rows as $i => $a) {
            foreach (array_slice($rows, $i + 1) as $b) {
                if (strcasecmp($a['subjectday'], $b['subjectday']) === 0 && $a['teacherid'] === $b['teacherid']
                    && $a['subtimestart'] < $b['subtimeend'] && $a['subtimeend'] > $b['subtimestart']) {
                    return $back('conflict', "'{$a['subjectname']}' overlaps with '{$b['subjectname']}' on {$a['subjectday']} for the same teacher.");
                }
            }
        }

        // ... nor any existing subject of the same teacher.
        foreach ($rows as $row) {
            $conflict = Subject::conflictingSubjectName($row['subjectday'], $row['subtimestart'], $row['subtimeend'], $row['teacherid']);
            if ($conflict !== null) {
                return $back('conflict', "'{$row['subjectname']}' overlaps with $conflict on {$row['subjectday']} for the same teacher.");
            }
        }

        try {
            DB::transaction(fn () => collect($rows)->each(fn ($row) => Subject::create($row)));
        } catch (UniqueConstraintViolationException) {
            return $back('duplicateCode', 'One of the subject codes already exists. Subject codes must be unique.');
        } catch (\Throwable $e) {
            Log::error('Add subject failed: '.$e->getMessage());

            return $back('conflict', 'The subjects could not be saved. Please check the details and try again.');
        }

        return redirect()->route('admin.subjects.index', ['status' => 'success']);
    }

    public function update(Request $request, int $subjectId)
    {
        $error = fn (string $msg) => redirect()->route('admin.subjects.index', ['status' => 'error', 'msg' => $msg]);

        $subject = Subject::find($subjectId);
        if (! $subject) {
            return $error('Subject not found');
        }

        $teacherId = trim((string) $request->input('teacherId'));
        if ($teacherId === '' || $teacherId === 'null') {
            return $error('A teacher must be assigned to this subject.');
        }

        $code = strtoupper(trim((string) $request->input('subjectCode')));
        if (! $this->isValidCode($code)) {
            return $error("Subject code '$code' is invalid. Use the format XXXF4 or XXXF5 (e.g. BIOF4).");
        }

        $name = trim((string) $request->input('subjectName'));
        if (! preg_match(self::NAME_PATTERN, $name)) {
            return $error("Subject name '$name' is invalid. Use only letters, numbers and spaces (e.g. Biology Form 4).");
        }
        $name = Format::sentenceCase($name);

        $day = in_array($request->input('subjectDay'), Subject::DAYS, true) ? $request->input('subjectDay') : $subject->subjectday;
        $start = Format::hm($request->input('timeStart'));
        $end = Format::hm($request->input('timeEnd'));
        if ($start === '' || $end === '' || $start >= $end) {
            return $error('The end time must be after the start time.');
        }
        if (! Subject::withinTimetableHours($start, $end)) {
            return $error('Classes must be between '.config('tms.day_start').' and '.config('tms.day_end').'.');
        }

        $fee = (float) str_replace(',', '', (string) $request->input('subjectFee', '0'));
        $slots = (int) $request->input('subjectAvailability', 0);
        if ($fee < 0) {
            return $error('The fee cannot be negative.');
        }

        // Slots cannot go below the number of students already holding a seat.
        $taken = Subject::occupancy($subjectId);
        if ($slots < max(1, $taken)) {
            return $error($taken > 0
                ? "$taken student(s) are already enrolled in this subject, so it needs at least $taken slots."
                : 'A subject needs at least 1 slot.');
        }

        // Same-teacher clash check, ignoring this subject's own current slot.
        $conflict = Subject::conflictingSubjectName($day, $start, $end, $teacherId, $subjectId);
        if ($conflict !== null) {
            return $error("Schedule conflict: overlaps with $conflict on $day for the same teacher.");
        }

        // Moving the class must not give an enrolled student two classes at the same time.
        $slotChanged = strcasecmp($day, $subject->subjectday) !== 0
            || $start !== Format::hm($subject->subtimestart) || $end !== Format::hm($subject->subtimeend);
        if ($slotChanged && ($studentClash = Subject::enrolledStudentClash($subjectId, $day, $start, $end))) {
            return $error("Cannot move this class: $studentClash at that time.");
        }

        // ... nor clash with another class in a package that contains it.
        if ($slotChanged) {
            $moved = (object) ['subjectname' => $name, 'subjectday' => $day, 'subtimestart' => $start, 'subtimeend' => $end];
            foreach ($subject->packages()->with('subjects')->get() as $package) {
                foreach ($package->subjects as $other) {
                    if ((int) $other->subjectid !== $subjectId && Subject::clashes($moved, $other)) {
                        return $error('Cannot move this class: it would clash with '.Subject::describeSlot($other)." in the package {$package->packagename}.");
                    }
                }
            }
        }

        try {
            $subject->update([
                'subjectcode' => 'SUB-'.$code,
                'subjectname' => $name,
                'subjectfee' => $fee,
                'subjectday' => $day,
                'subtimestart' => $start,
                'subtimeend' => $end,
                'subavailability' => $slots,
                'teacherid' => $teacherId,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $error('Duplicate Subject Code. One of the subject codes already exists. Subject codes must be unique.');
        }

        return redirect()->route('admin.subjects.index', ['status' => 'updated', 'name' => $name]);
    }

    /** Delete a subject (from the subject list, or from a teacher's subject tag when from=teacher). */
    public function destroy(Request $request, int $subjectId)
    {
        $name = (string) $request->input('name', 'Subject');
        $route = $request->input('from') === 'teacher' ? 'admin.teachers.index' : 'admin.subjects.index';

        // Blocked while any active student is still enrolled, directly or through a package.
        if (Subject::hasActiveEnrolment($subjectId)) {
            return redirect()->route($route, ['status' => 'error', 'msg' => 'Cannot delete subject: students are still enrolled in it.']);
        }

        $deleted = DB::transaction(function () use ($subjectId) {
            DB::table('packagesubjects')->where('subjectid', $subjectId)->delete();

            return Subject::whereKey($subjectId)->delete();
        });

        return $deleted
            ? redirect()->route($route, ['status' => 'deleted', 'name' => $name])
            : redirect()->route($route, ['status' => 'error', 'msg' => 'Subject not found']);
    }
}
