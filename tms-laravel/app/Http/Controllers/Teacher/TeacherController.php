<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Teacher dashboard, student list and subject list (with the per-subject student list). */
class TeacherController extends Controller
{
    public function dashboard()
    {
        $userId = Auth::id();
        $subjects = Subject::whereIn('teacherid', Teacher::where('userid', $userId)->select('teacherid'))->orderBy('subtimestart')->get();

        return view('teacher.dashboard', [
            'studentCount' => Teacher::studentCount($userId),
            'subjectCount' => $subjects->count(),
            'grids' => [
                'FORM 4' => Timetable::grid($subjects->where('subjectlevel', 'FORM 4')),
                'FORM 5' => Timetable::grid($subjects->where('subjectlevel', 'FORM 5')),
            ],
            'days' => Timetable::DAYS,
            'times' => Timetable::SLOTS,
        ]);
    }

    public function students()
    {
        // Long subject names are shortened for the table.
        $abbreviations = [
            'Additional Mathematics' => 'ADD', 'Mathematics' => 'MAT', 'Biology' => 'BIO', 'Physics' => 'PHY',
            'Chemistry' => 'CHE', 'English' => 'ENG', 'Science' => 'SCI', 'Sejarah' => 'SEJ',
            'Bahasa Melayu' => 'BM', 'History' => 'HIS', 'Prinsip Perakaunan' => 'ACC',
        ];

        $students = Teacher::studentList(Auth::id());

        return view('teacher.students', [
            'total' => $students->count(),
            'countF4' => $students->filter(fn ($s) => str_contains((string) $s->studentlevel, '4'))->count(),
            'countF5' => $students->filter(fn ($s) => str_contains((string) $s->studentlevel, '5'))->count(),
            'studentsData' => $students->map(function ($s) use ($abbreviations) {
                $subjects = (string) $s->subjects;
                foreach ($abbreviations as $long => $short) {
                    $subjects = str_replace($long, $short, $subjects);
                }

                return [
                    'name' => $s->username,
                    'level' => $s->studentlevel,
                    'phone' => $s->usercontact,
                    'img' => $s->has_photo
                        ? route('users.photo', $s->userid)
                        : 'https://img.freepik.com/free-vector/businessman-character-avatar-isolated_24877-60111.jpg',
                    'subjects' => $subjects,
                ];
            })->values(),
        ]);
    }

    public function subjects()
    {
        $userId = Auth::id();

        return view('teacher.subjects', [
            'subjectList' => Subject::forTeacherWithStudentCount($userId),
            'totalStudents' => Teacher::studentCount($userId),
            'f4Students' => Teacher::studentCount($userId, 'FORM 4'),
            'f5Students' => Teacher::studentCount($userId, 'FORM 5'),
        ]);
    }

    /** Table rows (HTML fragment) of the students enrolled in one of this teacher's subjects. */
    public function subjectStudents(Request $request, int $subjectId)
    {
        $ownsSubject = Subject::whereKey($subjectId)
            ->whereIn('teacherid', Teacher::where('userid', Auth::id())->select('teacherid'))
            ->exists();

        if (! $ownsSubject) {
            return response("<tr><td colspan='3' style='color:red;'>Invalid Subject ID.</td></tr>");
        }

        return view('teacher.partials.student-rows', ['students' => Subject::enrolledStudents($subjectId)]);
    }
}
