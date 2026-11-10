<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Payment;
use App\Models\Subject;
use App\Support\Timetable;
use Illuminate\Support\Facades\DB;

/** Admin dashboard: counts, revenue chart and timetable. */
class DashboardController extends Controller
{
    public function index()
    {
        $activeCount = fn (string $table) => DB::table($table.' as r')
            ->join('users as u', 'r.userid', '=', 'u.userid')
            ->where('u.statusflag', '<>', 'D')
            ->count();

        $revenue = Payment::monthlyRevenue();
        $subjects = Subject::orderBy('subtimestart')->get();

        return view('admin.dashboard', [
            'studentCount' => $activeCount('students'),
            'teacherCount' => $activeCount('teachers'),
            'totalAdmins' => Admin::listing()->count(),
            'revenueMonths' => $revenue->isEmpty() ? ['No Data'] : $revenue->pluck('label')->all(),
            'revenueAmounts' => $revenue->isEmpty() ? [0] : $revenue->pluck('total')->map(fn ($v) => (float) $v)->all(),
            'grids' => [
                'FORM 4' => Timetable::grid($subjects->where('subjectlevel', 'FORM 4'), false),
                'FORM 5' => Timetable::grid($subjects->where('subjectlevel', 'FORM 5'), false),
            ],
            'days' => Timetable::DAYS,
            'times' => Timetable::SLOTS,
        ]);
    }
}
