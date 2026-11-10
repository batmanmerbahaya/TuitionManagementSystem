<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectRegistration;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('proof.png', file_get_contents(public_path('images/tick.png')));
    }

    /** A Form 4 class on Monday 09:00-11:00 - it overlaps Mathematics form 4 (Monday 08:00-10:00). */
    private function clashingSubject(): Subject
    {
        return Subject::create([
            'subjectcode' => 'SUB-ADDF4', 'subjectname' => 'Additional mathematics', 'subjectlevel' => 'FORM 4',
            'subjectfee' => 70, 'subjectday' => 'Monday', 'subtimestart' => '09:00', 'subtimeend' => '11:00',
            'subavailability' => 10, 'teacherid' => 'T002',
        ]);
    }

    public function test_registering_a_subject_and_a_package_creates_this_months_bills(): void
    {
        $this->asStudent();

        $this->assertSame('success', $this->register(['type' => 'subject', 'subjectIds' => [4]]));
        $this->assertSame('success', $this->register(['type' => 'package', 'id' => 1]));

        $bills = Payment::unpaidBills($this->studentId('student4@anjungilmu.com'));
        $this->assertEqualsCanonicalizing([60.0, 15.0, 120.0], $bills->map(fn ($b) => (float) $b->paymentamount)->all());
        $this->assertSame([now()->format('Y-m')], $bills->pluck('billingperiod')->unique()->values()->all());

        $this->get('/student/payments')
            ->assertSee('RM 195.00')
            ->assertSee('One-time registration fee')
            ->assertSee('Tuition for '.strtoupper(now()->format('F Y')));
    }

    public function test_a_class_that_clashes_with_the_students_timetable_is_refused(): void
    {
        $clashing = $this->clashingSubject();
        $this->asStudent();

        $this->assertSame('success', $this->register(['type' => 'subject', 'subjectIds' => [1]]));
        $reply = $this->register(['type' => 'subject', 'subjectIds' => [$clashing->subjectid]]);

        $this->assertStringContainsString('Timetable clash', $reply);
        $this->assertStringContainsString('Mathematics form 4', $reply);
        $this->assertSame(1, SubjectRegistration::where('studentid', $this->studentId('student4@anjungilmu.com'))->count());
    }

    public function test_two_clashing_classes_picked_together_are_refused(): void
    {
        $clashing = $this->clashingSubject();
        $this->asStudent();

        $this->assertStringContainsString('Timetable clash', $this->register(['type' => 'subject', 'subjectIds' => [1, $clashing->subjectid]]));
    }

    public function test_a_package_that_clashes_with_the_students_classes_is_refused(): void
    {
        $clashing = $this->clashingSubject();
        $this->asStudent();

        $this->assertSame('success', $this->register(['type' => 'subject', 'subjectIds' => [$clashing->subjectid]]));
        // Package 1 contains Mathematics form 4 (Monday 08:00-10:00).
        $this->assertStringContainsString('Timetable clash', $this->register(['type' => 'package', 'id' => 1]));
    }

    public function test_a_full_subject_cannot_be_taken(): void
    {
        Subject::whereKey(4)->update(['subavailability' => 1]);

        // Another Form 4 student takes the only seat.
        $this->post('/signup', ['userName' => 'Other Student', 'email' => 'other@example.com', 'phone' => '0123456789', 'password' => 'Password1', 'level' => 'FORM 4']);
        $this->asStudent('other@example.com');
        $this->assertSame('success', $this->register(['type' => 'subject', 'subjectIds' => [4]]));
        $this->logout();

        $this->asStudent();
        $this->assertStringContainsString('already full', $this->register(['type' => 'subject', 'subjectIds' => [4]]));
    }

    public function test_the_same_subject_cannot_be_taken_twice(): void
    {
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);

        // Subject 2 (Science form 4) is already inside package 1.
        $this->assertStringContainsString('already taking', $this->register(['type' => 'subject', 'subjectIds' => [2]]));
        $this->assertStringContainsString('already registered', $this->register(['type' => 'package', 'id' => 1]));
    }

    public function test_an_unpaid_registration_can_be_dropped_by_the_student(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $regId = SubjectRegistration::where('studentid', $this->studentId('student4@anjungilmu.com'))->value('subregid');

        $this->post("/student/registrations/$regId/drop")->assertRedirectContains('status=dropped');

        $this->assertSame('D', SubjectRegistration::find($regId)->subregstatusflag);
        $this->assertSame(0, Payment::where('subregid', $regId)->count());
    }

    public function test_after_paying_only_an_admin_can_drop(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $studentId = $this->studentId('student4@anjungilmu.com');
        $regId = SubjectRegistration::where('studentid', $studentId)->value('subregid');

        $ids = Payment::unpaidBills($studentId)->pluck('paymentid')->implode(',');
        $this->post('/student/payments/upload', ['joinedIds' => $ids, 'proofFile' => $this->proof()])->assertRedirectContains('status=paymentSuccess');

        $this->post("/student/registrations/$regId/drop")->assertRedirectContains('error=pendingVerify');
        $this->assertSame('A', SubjectRegistration::find($regId)->subregstatusflag);
    }

    public function test_a_student_cannot_attach_proof_to_someone_elses_bills(): void
    {
        $this->asStudent('student5@anjungilmu.com');
        $this->register(['type' => 'package', 'id' => 2]);
        $theirBills = Payment::unpaidBills($this->studentId('student5@anjungilmu.com'))->pluck('paymentid');
        $this->logout();

        $this->asStudent();
        $this->post('/student/payments/upload', ['joinedIds' => $theirBills->implode(','), 'proofFile' => $this->proof()])
            ->assertRedirectContains('error=failed');

        $this->assertSame(['UNPAID'], Payment::whereIn('paymentid', $theirBills)->pluck('paymentstatus')->unique()->values()->all());
    }

    public function test_only_pdf_and_image_proofs_are_accepted(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $ids = Payment::unpaidBills($this->studentId('student4@anjungilmu.com'))->pluck('paymentid')->implode(',');

        $this->post('/student/payments/upload', ['joinedIds' => $ids, 'proofFile' => UploadedFile::fake()->createWithContent('virus.exe', 'MZ')])
            ->assertRedirectContains('error=invalidFile');
    }
}
