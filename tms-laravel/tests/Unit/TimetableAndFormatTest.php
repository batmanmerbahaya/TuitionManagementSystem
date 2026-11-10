<?php

namespace Tests\Unit;

use App\Support\Format;
use App\Support\Timetable;
use PHPUnit\Framework\TestCase;

class TimetableAndFormatTest extends TestCase
{
    private function subject(string $name, string $day, string $start, string $end): object
    {
        return (object) ['subjectname' => $name, 'subjectday' => $day, 'subtimestart' => $start.':00', 'subtimeend' => $end.':00'];
    }

    public function test_a_class_appears_in_every_slot_it_overlaps(): void
    {
        $grid = Timetable::grid([$this->subject('Mathematics', 'Monday', '09:00', '11:00')]);

        $this->assertSame('MAT', $grid['MON'][0][0]['code']);   // 08:00 slot
        $this->assertSame('MAT', $grid['MON'][1][0]['code']);   // 10:00 slot
        $this->assertSame([], $grid['MON'][2]);
        $this->assertSame('Mathematics (09:00 - 11:00)', $grid['MON'][0][0]['title']);
    }

    public function test_two_classes_in_the_same_slot_are_both_kept(): void
    {
        $grid = Timetable::grid([
            $this->subject('Biology', 'Thursday', '16:00', '18:00'),
            $this->subject('Physics', 'Thursday', '16:30', '17:30'),
        ]);

        $this->assertSame(['BIO', 'PHY'], array_column($grid['THU'][4], 'code'));
    }

    public function test_classes_outside_the_grid_hours_are_ignored(): void
    {
        $grid = Timetable::grid([$this->subject('English', 'Sunday', '22:00', '23:00')]);

        $this->assertSame([], array_merge(...$grid['SUN']));
    }

    public function test_phone_numbers(): void
    {
        $this->assertSame('0123456789', Format::normalisePhone('12-345 6789'));
        $this->assertSame('0123456789', Format::normalisePhone('012-345 6789'));
        $this->assertSame('+60 12-3456 789', Format::phone('0123456789'));
        $this->assertSame('12-3456 789', Format::phone('0123456789', false));
    }

    public function test_badge_codes(): void
    {
        $this->assertSame('ADD', Format::badgeCode('Additional Mathematics'));
        $this->assertSame('MAT', Format::badgeCode('Additional Mathematics', false));
        $this->assertSame('MAL', Format::badgeCode('Bahasa Melayu'));
        $this->assertSame('GEO', Format::badgeCode('Geography'));
    }
}
