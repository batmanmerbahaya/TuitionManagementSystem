<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectRegistration;
use Tests\TestCase;

class SubjectPackageRulesTest extends TestCase
{
    /** Edit form for subject 4 (Biology form 4, Thursday 16:00-18:00, 15 slots, teacher T002). */
    private function biologyForm(array $changes = []): array
    {
        return $changes + [
            'subjectCode' => 'BIOF4', 'subjectName' => 'Biology form 4', 'subjectFee' => '60',
            'subjectDay' => 'Thursday', 'timeStart' => '16:00', 'timeEnd' => '18:00',
            'subjectAvailability' => '15', 'teacherId' => 'T002',
        ];
    }

    private function newSubjectForm(array $changes = []): array
    {
        return array_replace([
            'code' => ['ADDMF4'], 'name' => ['Additional maths'], 'level' => ['FORM 4'], 'fee' => ['70'],
            'teacher' => ['T001'], 'day' => ['Friday'], 'avail' => ['10'], 'start' => ['18:00'], 'end' => ['20:00'],
        ], $changes);
    }

    public function test_slots_cannot_go_below_the_students_already_enrolled(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $this->logout();

        $this->asAdmin();
        $this->put('/admin/subjects/4', $this->biologyForm(['subjectAvailability' => '0']))->assertRedirectSays('already enrolled');
        $this->put('/admin/subjects/4', $this->biologyForm(['subjectAvailability' => '1']))->assertRedirectContains('status=updated');
        $this->assertSame(1, Subject::find(4)->subavailability);
    }

    public function test_classes_must_be_inside_opening_hours(): void
    {
        $this->asAdmin();

        $this->post('/admin/subjects', $this->newSubjectForm(['start' => ['07:00'], 'end' => ['09:00']]))->assertRedirectContains('status=conflict');
        $this->put('/admin/subjects/4', $this->biologyForm(['timeStart' => '21:00', 'timeEnd' => '23:00']))->assertRedirectContains('status=error');

        $this->post('/admin/subjects', $this->newSubjectForm())->assertRedirectContains('status=success');
    }

    public function test_a_class_cannot_be_moved_onto_an_enrolled_students_other_class(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [1, 4]]); // Mon 08-10 and Thu 16-18
        $this->logout();

        $this->asAdmin();
        // Moving Biology to Monday 09:00 would clash with the student's Mathematics.
        $this->put('/admin/subjects/4', $this->biologyForm(['subjectDay' => 'Monday', 'timeStart' => '09:00', 'timeEnd' => '11:00']))
            ->assertRedirectSays('Cannot move this class');
        $this->assertSame('Thursday', Subject::find(4)->subjectday);

        // Friday is free for everyone.
        $this->put('/admin/subjects/4', $this->biologyForm(['subjectDay' => 'Friday']))->assertRedirectContains('status=updated');
    }

    public function test_a_package_class_cannot_be_moved_onto_another_class_in_the_same_package(): void
    {
        $this->asAdmin();
        // Science form 4 (subject 2, Tue 10-12) is in package 1 together with Mathematics form 4 (Mon 08-10).
        $this->put('/admin/subjects/2', [
            'subjectCode' => 'SCIF4', 'subjectName' => 'Science form 4', 'subjectFee' => '50', 'subjectDay' => 'Monday',
            'timeStart' => '09:00', 'timeEnd' => '11:00', 'subjectAvailability' => '20', 'teacherId' => 'T002',
        ])->assertRedirectSays('Core package form 4');
    }

    public function test_package_subjects_cannot_change_while_students_are_registered(): void
    {
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);
        $this->logout();

        $this->asAdmin();
        $form = ['packageCode' => 'CORF4', 'packageName' => 'Core package form 4', 'price' => '130'];

        $this->put('/admin/packages/1', $form + ['selectedSubjects' => [1, 2]])->assertRedirectSays('cannot be changed');
        $this->assertSame([1, 2, 3], Package::find(1)->subjects()->pluck('subjects.subjectid')->map(fn ($id) => (int) $id)->sort()->values()->all());

        // Changing only the fee is fine.
        $this->put('/admin/packages/1', $form + ['selectedSubjects' => [1, 2, 3]])->assertRedirectContains('status=updated');
        $this->assertSame(130.0, (float) Package::find(1)->packagefee);
    }

    public function test_a_package_cannot_contain_classes_at_the_same_time(): void
    {
        $clash = Subject::create([
            'subjectcode' => 'SUB-ADDF4', 'subjectname' => 'Additional mathematics', 'subjectlevel' => 'FORM 4', 'subjectfee' => 70,
            'subjectday' => 'Monday', 'subtimestart' => '09:00', 'subtimeend' => '11:00', 'subavailability' => 10, 'teacherid' => 'T002',
        ]);

        $this->asAdmin();
        $this->post('/admin/packages', ['level' => 'FORM 4', 'packageCode' => 'MATHF4', 'packageName' => 'Maths duo', 'price' => '110', 'selectedSubjects' => [1, $clash->subjectid]])
            ->assertRedirectSays('cannot be in one package');
        $this->post('/admin/packages', ['level' => 'FORM 4', 'packageCode' => 'MATHF4', 'packageName' => 'Maths duo', 'price' => '110', 'selectedSubjects' => [1, 4]])
            ->assertRedirectContains('status=success');
    }

    public function test_students_who_dropped_earlier_do_not_block_deleting_a_package(): void
    {
        // Paid for last month, then dropped this month.
        $this->travelTo(now()->startOfMonth()->subMonth()->addDays(3));
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);
        $regId = SubjectRegistration::where('studentid', $this->studentId('student4@anjungilmu.com'))->value('subregid');
        Payment::where('subregid', $regId)->update(['paymentstatus' => 'APPROVED', 'adminid' => 'A001']);
        $this->logout();
        $this->travelBack();

        $this->asAdmin();
        $this->delete('/admin/packages/1', ['name' => 'Core'])->assertRedirectContains('status=error'); // still registered
        $this->post("/admin/registrations/$regId/drop");
        $this->delete('/admin/packages/1', ['name' => 'Core'])->assertRedirectContains('status=deleted');

        // The paid history is kept.
        $this->assertSame(['APPROVED'], Payment::where('subregid', $regId)->pluck('paymentstatus')->unique()->values()->all());
    }

    public function test_subject_with_enrolled_students_cannot_be_deleted(): void
    {
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);
        $this->logout();

        $this->asAdmin();
        $this->delete('/admin/subjects/1', ['name' => 'Mathematics form 4'])->assertRedirectContains('status=error');
        $this->delete('/admin/subjects/4', ['name' => 'Biology form 4'])->assertRedirectContains('status=deleted');
    }
}
