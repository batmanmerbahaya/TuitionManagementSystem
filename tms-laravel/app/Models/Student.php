<?php

namespace App\Models;

use App\Support\Timetable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Student extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'studentid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['studentid', 'studentlevel', 'userid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(SubjectRegistration::class, 'studentid', 'studentid');
    }

    /**
     * Active students with the subjects they are enrolled in
     * (direct registrations and subjects inside registered packages).
     */
    public static function listing(): Collection
    {
        $students = collect(DB::select(
            "SELECT s.studentid, u.userid, u.username, u.usercontact, u.useremail, s.studentlevel,
                    (u.userimage IS NOT NULL) AS has_photo
               FROM students s
               JOIN users u ON s.userid = u.userid
              WHERE (u.statusflag IS NULL OR u.statusflag <> 'D')
              ORDER BY s.studentid ASC"
        ));

        $subjectNames = collect(DB::select(
            "SELECT sr.studentid, sub.subjectname
               FROM subjectregistrations sr
               JOIN subjects sub
                 ON (sub.subjectid = sr.subjectid
                     OR sub.subjectid IN (SELECT ps.subjectid FROM packagesubjects ps WHERE ps.packageid = sr.packageid))
              WHERE sr.subregstatusflag <> 'D'
              ORDER BY sub.subjectname"
        ))->groupBy('studentid');

        return $students->map(function ($s) use ($subjectNames) {
            $s->subjects = ($subjectNames[$s->studentid] ?? collect())->pluck('subjectname')->unique()->values()->all();

            return $s;
        });
    }

    /** Number of subjects the student is registered for (packages count each subject). */
    public static function registeredSubjectCount(string $studentId): int
    {
        return (int) DB::selectOne(
            "SELECT COUNT(DISTINCT s.subjectid) AS c
               FROM subjectregistrations sr
               LEFT JOIN packagesubjects ps ON sr.packageid = ps.packageid
               LEFT JOIN subjects s ON (sr.subjectid = s.subjectid OR ps.subjectid = s.subjectid)
              WHERE sr.studentid = ? AND sr.subregstatusflag = 'A'",
            [$studentId]
        )->c;
    }

    /** Bills the student still has to act on. */
    public static function actionablePaymentCount(string $studentId): int
    {
        return (int) DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM payments p
               JOIN subjectregistrations sr ON p.subregid = sr.subregid
              WHERE sr.studentid = ? AND p.paymentstatus IN ('REJECTED', 'UNPAID')",
            [$studentId]
        )->c;
    }

    /**
     * The student's classes laid out as day x 2-hour slots
     * (see App\Support\Timetable).
     */
    public static function timetableGrid(string $studentId): array
    {
        $rows = DB::select(
            "SELECT DISTINCT s.subjectid, s.subjectday, s.subtimestart, s.subtimeend, s.subjectname
               FROM subjectregistrations sr
               LEFT JOIN packagesubjects ps ON sr.packageid = ps.packageid
               LEFT JOIN subjects s ON (sr.subjectid = s.subjectid OR ps.subjectid = s.subjectid)
              WHERE sr.studentid = ? AND sr.subregstatusflag = 'A' AND s.subjectid IS NOT NULL",
            [$studentId]
        );

        return Timetable::grid($rows);
    }
}
