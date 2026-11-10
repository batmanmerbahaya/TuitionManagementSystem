<?php

namespace App\Support;

/**
 * Builds the weekly timetable grids (7 days x seven 2-hour slots: 08:00, 10:00 ... 20:00) used on the
 * admin, teacher and student dashboards. A class appears in every slot it overlaps (a 09:00-11:00
 * class shows in both the 08:00 and 10:00 columns) and several classes can share a slot.
 */
class Timetable
{
    public const DAYS = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];

    public const SLOTS = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'];

    /**
     * @param  iterable<object>  $subjects  rows with subjectname, subjectday, subtimestart, subtimeend
     * @param  bool  $extendedCodes  badge code mapping (see Format::badgeCode)
     * @return array<string, array<int, array<int, array{code: string, title: string}>>>
     */
    public static function grid(iterable $subjects, bool $extendedCodes = true): array
    {
        $grid = [];
        foreach (self::DAYS as $day) {
            $grid[$day] = array_fill(0, count(self::SLOTS), []);
        }

        foreach ($subjects as $s) {
            $day = strtoupper(substr(trim((string) $s->subjectday), 0, 3));
            if (! isset($grid[$day]) || ! $s->subtimestart) {
                continue;
            }

            $start = substr($s->subtimestart, 0, 5);
            $end = $s->subtimeend ? substr($s->subtimeend, 0, 5) : $start;
            $entry = [
                'code' => Format::badgeCode($s->subjectname, $extendedCodes),
                'title' => "{$s->subjectname} ({$start} - {$end})",
            ];

            foreach (self::SLOTS as $i => $slotStart) {
                $slotEnd = sprintf('%02d:00', (int) substr($slotStart, 0, 2) + 2);
                $overlaps = $start < $slotEnd && ($end > $slotStart || ($end === $start && $start >= $slotStart));
                if ($overlaps) {
                    $grid[$day][$i][] = $entry;
                }
            }
        }

        return $grid;
    }
}
