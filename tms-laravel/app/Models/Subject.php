<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    protected $table = 'subjects';
    protected $primaryKey = 'subjectid';
    public $timestamps = false;

    protected $fillable = [
        'subjectcode', 'subjectname', 'subjectlevel', 'subjectfee', 'subjectday',
        'subtimestart', 'subtimeend', 'subavailability', 'teacherid',
    ];

    /**
     * Seats taken in subject `s`: every non-dropped registration of the subject itself
     * PLUS every non-dropped registration of a package that contains it. Unpaid registrations
     * count too, so a seat is held as soon as a student registers.
     */
    public const OCCUPANCY_SQL = "(SELECT COUNT(*) FROM subjectregistrations sr
        WHERE sr.subregstatusflag <> 'D'
          AND (sr.subjectid = s.subjectid
               OR sr.packageid IN (SELECT ps_occ.packageid FROM packagesubjects ps_occ WHERE ps_occ.subjectid = s.subjectid)))";

    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacherid', 'teacherid');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'packagesubjects', 'subjectid', 'packageid');
    }

    /** All subjects with their occupancy, for the admin subject list. */
    public static function listingWithOccupancy(): Collection
    {
        return collect(DB::select(
            'SELECT s.*, '.self::OCCUPANCY_SQL.' AS registered_count
               FROM subjects s
              ORDER BY s.subjectlevel, s.subjectname'
        ));
    }

    /** Subjects of the student's level they are not already taking. */
    public static function availableForStudent(string $level, string $studentId): Collection
    {
        return collect(DB::select(
            'SELECT s.*, '.self::OCCUPANCY_SQL." AS registered_count
               FROM subjects s
              WHERE s.subjectlevel = ?
                AND s.subjectid NOT IN (
                    SELECT subjectid FROM subjectregistrations
                     WHERE studentid = ? AND subregstatusflag <> 'D' AND subjectid IS NOT NULL)
                AND s.subjectid NOT IN (
                    SELECT ps.subjectid FROM packagesubjects ps
                      JOIN subjectregistrations sr ON ps.packageid = sr.packageid
                     WHERE sr.studentid = ? AND sr.subregstatusflag <> 'D')
              ORDER BY s.subjectid",
            [$level, $studentId, $studentId]
        ));
    }

    /** Whether every seat of the subject is taken. */
    public static function isFull(int $subjectId): bool
    {
        $row = DB::selectOne(
            'SELECT s.subavailability, '.self::OCCUPANCY_SQL.' AS registered_count FROM subjects s WHERE s.subjectid = ?',
            [$subjectId]
        );

        return $row && $row->registered_count >= $row->subavailability;
    }

    /**
     * Another subject taught by the same teacher on the
     * same day whose time range overlaps [start, end). Times are "HH:MM" strings.
     */
    public static function conflictingSubjectName(string $day, string $start, string $end, string $teacherId, int $excludeSubjectId = -1): ?string
    {
        $row = DB::selectOne(
            "SELECT subjectname, TIME_FORMAT(subtimestart, '%H:%i') AS start_t, TIME_FORMAT(subtimeend, '%H:%i') AS end_t
               FROM subjects
              WHERE UPPER(TRIM(subjectday)) = UPPER(TRIM(?))
                AND teacherid = ?
                AND subjectid <> ?
                AND ? < TIME_FORMAT(subtimeend, '%H:%i')
                AND ? > TIME_FORMAT(subtimestart, '%H:%i')
              LIMIT 1",
            [$day, $teacherId, $excludeSubjectId, $start, $end]
        );

        return $row ? "{$row->subjectname} ({$row->start_t} - {$row->end_t})" : null;
    }

    /** True if any active (non-deleted) student is enrolled, directly or through a package. */
    public static function hasActiveEnrolment(int $subjectId): bool
    {
        return DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM subjectregistrations sr
               JOIN students st ON sr.studentid = st.studentid
               JOIN users u ON st.userid = u.userid
              WHERE sr.subregstatusflag <> 'D'
                AND (u.statusflag IS NULL OR u.statusflag <> 'D')
                AND (sr.subjectid = ? OR sr.packageid IN (SELECT packageid FROM packagesubjects WHERE subjectid = ?))",
            [$subjectId, $subjectId]
        )->c > 0;
    }

    /** A teacher's subjects with the number of active students in each. */
    public static function forTeacherWithStudentCount(string $teacherUserId): Collection
    {
        return collect(DB::select(
            "SELECT s.*,
                    (SELECT COUNT(DISTINCT sr.studentid)
                       FROM subjectregistrations sr
                       JOIN students st ON sr.studentid = st.studentid
                       JOIN users u ON st.userid = u.userid
                      WHERE (sr.subjectid = s.subjectid
                             OR sr.packageid IN (SELECT ps.packageid FROM packagesubjects ps WHERE ps.subjectid = s.subjectid))
                        AND u.statusflag = 'A'
                        AND sr.subregstatusflag = 'A') AS student_count
               FROM subjects s
               JOIN teachers t ON s.teacherid = t.teacherid
              WHERE t.userid = ?
              ORDER BY s.subjectlevel, s.subjectname",
            [$teacherUserId]
        ));
    }

    /** Seats currently taken in one subject (same counting rule as OCCUPANCY_SQL). */
    public static function occupancy(int $subjectId): int
    {
        return (int) DB::selectOne(
            'SELECT '.self::OCCUPANCY_SQL.' AS c FROM subjects s WHERE s.subjectid = ?',
            [$subjectId]
        )?->c;
    }

    /** True when a class sits inside the timetable hours (config tms.day_start / day_end). */
    public static function withinTimetableHours(string $start, string $end): bool
    {
        return $start >= config('tms.day_start', '08:00') && $end <= config('tms.day_end', '22:00');
    }

    /** Two classes clash when they are on the same day and their times overlap. */
    public static function clashes(object $a, object $b): bool
    {
        return strcasecmp(trim($a->subjectday), trim($b->subjectday)) === 0
            && substr($a->subtimestart, 0, 5) < substr($b->subtimeend, 0, 5)
            && substr($a->subtimeend, 0, 5) > substr($b->subtimestart, 0, 5);
    }

    public static function describeSlot(object $s): string
    {
        return "{$s->subjectname} ({$s->subjectday} ".substr($s->subtimestart, 0, 5).' - '.substr($s->subtimeend, 0, 5).')';
    }

    /** First pair of clashing classes inside one set of subjects (e.g. a package), or null. */
    public static function firstClashWithin(iterable $subjects): ?string
    {
        $list = array_values(is_array($subjects) ? $subjects : iterator_to_array($subjects));
        foreach ($list as $i => $a) {
            foreach (array_slice($list, $i + 1) as $b) {
                if (self::clashes($a, $b)) {
                    return self::describeSlot($a).' clashes with '.self::describeSlot($b).'.';
                }
            }
        }

        return null;
    }

    /**
     * Would registering $newSubjectIds give the student two classes at the same time?
     * Checks the new subjects against the student's current (non-dropped) subjects, direct
     * or through packages, and against each other. Returns a message, or null when there is no clash.
     */
    public static function studentScheduleClash(string $studentId, array $newSubjectIds): ?string
    {
        $new = self::whereIn('subjectid', $newSubjectIds)->get()->all();

        $current = DB::select(
            "SELECT DISTINCT s.subjectid, s.subjectname, s.subjectday, s.subtimestart, s.subtimeend
               FROM subjectregistrations sr
               LEFT JOIN packagesubjects ps ON sr.packageid = ps.packageid
               JOIN subjects s ON (s.subjectid = sr.subjectid OR s.subjectid = ps.subjectid)
              WHERE sr.studentid = ? AND sr.subregstatusflag <> 'D'",
            [$studentId]
        );

        foreach ($new as $n) {
            foreach ($current as $c) {
                if ((int) $c->subjectid !== (int) $n->subjectid && self::clashes($n, $c)) {
                    return self::describeSlot($n).' clashes with your class '.self::describeSlot($c).'.';
                }
            }
        }

        return self::firstClashWithin($new);
    }

    /**
     * If subject $subjectId moved to $day $start-$end, would any student enrolled in it get two classes
     * at the same time? Returns a description of the first such clash, or null.
     */
    public static function enrolledStudentClash(int $subjectId, string $day, string $start, string $end): ?string
    {
        $row = DB::selectOne(
            "SELECT u.username, o.subjectname, o.subjectday, o.subtimestart, o.subtimeend
               FROM subjectregistrations r1
               LEFT JOIN packagesubjects p1 ON r1.packageid = p1.packageid
               JOIN students st ON st.studentid = r1.studentid
               JOIN users u ON u.userid = st.userid
               JOIN subjectregistrations r2 ON r2.studentid = r1.studentid AND r2.subregstatusflag <> 'D'
               LEFT JOIN packagesubjects p2 ON r2.packageid = p2.packageid
               JOIN subjects o ON (o.subjectid = r2.subjectid OR o.subjectid = p2.subjectid)
              WHERE r1.subregstatusflag <> 'D'
                AND (r1.subjectid = ? OR p1.subjectid = ?)
                AND o.subjectid <> ?
                AND UPPER(TRIM(o.subjectday)) = UPPER(TRIM(?))
                AND ? < TIME_FORMAT(o.subtimeend, '%H:%i')
                AND ? > TIME_FORMAT(o.subtimestart, '%H:%i')
              LIMIT 1",
            [$subjectId, $subjectId, $subjectId, $day, $start, $end]
        );

        return $row ? "{$row->username} also takes ".self::describeSlot($row) : null;
    }

    /** Active students enrolled directly or via a package. */
    public static function enrolledStudents(int $subjectId): Collection
    {
        return collect(DB::select(
            "SELECT DISTINCT u.username, u.usercontact, s.studentid, s.studentlevel
               FROM students s
               JOIN users u ON s.userid = u.userid
               JOIN subjectregistrations sr ON s.studentid = sr.studentid
              WHERE (sr.subjectid = ? OR sr.packageid IN (SELECT ps.packageid FROM packagesubjects ps WHERE ps.subjectid = ?))
                AND u.statusflag = 'A'
                AND sr.subregstatusflag = 'A'
              ORDER BY u.username",
            [$subjectId, $subjectId]
        ));
    }
}
