<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Ids;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Optional sample data for trying the system out:
 *   php artisan db:seed --class=DemoSeeder
 *
 * Logins (password for all: Password1):
 *   teacher1@anjungilmu.com, teacher2@anjungilmu.com, student4@anjungilmu.com (Form 4), student5@anjungilmu.com (Form 5)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        if (User::where('useremail', 'teacher1@anjungilmu.com')->exists()) {
            $this->command?->info('Demo data already present.');

            return;
        }

        DB::transaction(function () {
            $teacher = function (string $name, string $email, string $phone) {
                $user = User::createAccount($name, $email, $phone, 'Password1', null);

                return Teacher::create(['teacherid' => Ids::nextTeacher(), 'userid' => $user->userid]);
            };
            $t1 = $teacher('Aminah Binti Yusof', 'teacher1@anjungilmu.com', '0121112222');
            $t2 = $teacher('Rahman Bin Ali', 'teacher2@anjungilmu.com', '0133334444');

            foreach ([['Ali Bin Ahmad', 'student4@anjungilmu.com', 'FORM 4'], ['Siti Nurhaliza', 'student5@anjungilmu.com', 'FORM 5']] as [$name, $email, $level]) {
                $user = User::createAccount($name, $email, '0145556666', 'Password1', null);
                Student::create(['studentid' => Ids::nextStudent(), 'userid' => $user->userid, 'studentlevel' => $level]);
            }

            $subjects = [
                // code, name, level, fee, day, start, end, slots, teacher
                ['SUB-MATF4', 'Mathematics form 4', 'FORM 4', 50, 'Monday', '08:00', '10:00', 20, $t1],
                ['SUB-SCIF4', 'Science form 4', 'FORM 4', 50, 'Tuesday', '10:00', '12:00', 20, $t2],
                ['SUB-ENGF4', 'English form 4', 'FORM 4', 45, 'Wednesday', '14:00', '16:00', 20, $t1],
                ['SUB-BIOF4', 'Biology form 4', 'FORM 4', 60, 'Thursday', '16:00', '18:00', 15, $t2],
                ['SUB-MATF5', 'Mathematics form 5', 'FORM 5', 55, 'Monday', '10:00', '12:00', 20, $t1],
                ['SUB-SCIF5', 'Science form 5', 'FORM 5', 55, 'Tuesday', '14:00', '16:00', 20, $t2],
                ['SUB-PHYF5', 'Physics form 5', 'FORM 5', 65, 'Friday', '08:00', '10:00', 15, $t2],
                ['SUB-CHEF5', 'Chemistry form 5', 'FORM 5', 65, 'Saturday', '10:00', '12:00', 15, $t1],
            ];

            $ids = [];
            foreach ($subjects as [$code, $name, $level, $fee, $day, $start, $end, $slots, $t]) {
                $ids[$code] = Subject::create([
                    'subjectcode' => $code, 'subjectname' => $name, 'subjectlevel' => $level, 'subjectfee' => $fee,
                    'subjectday' => $day, 'subtimestart' => $start, 'subtimeend' => $end,
                    'subavailability' => $slots, 'teacherid' => $t->teacherid,
                ])->subjectid;
            }

            Package::create(['packagecode' => 'PKG-CORF4', 'packagename' => 'Core package form 4', 'packagefee' => 120, 'packagelevel' => 'FORM 4'])
                ->subjects()->sync([$ids['SUB-MATF4'], $ids['SUB-SCIF4'], $ids['SUB-ENGF4']]);
            Package::create(['packagecode' => 'PKG-SCIF5', 'packagename' => 'Science stream form 5', 'packagefee' => 160, 'packagelevel' => 'FORM 5'])
                ->subjects()->sync([$ids['SUB-PHYF5'], $ids['SUB-CHEF5'], $ids['SUB-SCIF5']]);
        });

        $this->command?->info('Demo teachers, students, subjects and packages created (password: Password1).');
    }
}
