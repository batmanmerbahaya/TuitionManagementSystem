<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonthlyBillingTest extends TestCase
{
    private function nextPeriod(): string
    {
        return now()->startOfMonth()->addMonth()->format('Y-m');
    }

    /** student4: Biology form 4 (RM 60) + package 1 (RM 120); student5: package 2 (RM 160). */
    private function enrolStudents(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $this->register(['type' => 'package', 'id' => 1]);
        $this->logout();

        $this->asStudent('student5@anjungilmu.com');
        $this->register(['type' => 'package', 'id' => 2]);
        $this->logout();
    }

    public function test_admin_generates_next_months_bills_once(): void
    {
        $this->enrolStudents();
        $this->asAdmin();

        $this->get('/admin/payments')->assertSee('Generate Bills');

        $this->post('/admin/payments/generate-bills', ['period' => $this->nextPeriod()])
            ->assertRedirectContains('status=billed')
            ->assertRedirectContains('count=3');

        // Clicking again never bills anyone twice.
        $this->post('/admin/payments/generate-bills', ['period' => $this->nextPeriod()])->assertRedirectContains('count=0');

        $this->assertEqualsCanonicalizing(
            [60.0, 120.0, 160.0],
            Payment::where('billingperiod', $this->nextPeriod())->pluck('paymentamount')->map(fn ($v) => (float) $v)->all()
        );
        // No new registration fee - that is only charged once.
        $this->assertSame(0, Payment::where('billingperiod', $this->nextPeriod())->where('paymenttype', 'REGISTRATION')->count());
    }

    public function test_months_too_far_ahead_are_refused(): void
    {
        $this->asAdmin();

        $this->post('/admin/payments/generate-bills', ['period' => now()->addMonths(3)->format('Y-m')])->assertRedirectContains('error=period');
        $this->post('/admin/payments/generate-bills', ['period' => 'garbage'])->assertRedirectContains('error=period');
    }

    public function test_dropped_registrations_and_deleted_students_are_not_billed(): void
    {
        $this->enrolStudents();

        $biology = SubjectRegistration::where('studentid', $this->studentId('student4@anjungilmu.com'))->where('subjectid', 4)->first();
        SubjectRegistration::drop($biology->subregid);
        DB::table('users')->where('useremail', 'student5@anjungilmu.com')->update(['statusflag' => 'D']);

        $this->assertSame(1, Payment::generateMonthlyBills($this->nextPeriod())); // only student4's package
    }

    public function test_the_new_bill_uses_the_current_fee(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        Subject::whereKey(4)->update(['subjectfee' => 80]);

        Payment::generateMonthlyBills($this->nextPeriod());

        $this->assertSame(80.0, (float) Payment::where('billingperiod', $this->nextPeriod())->value('paymentamount'));
    }

    public function test_the_console_command(): void
    {
        $this->enrolStudents();

        $this->artisan('tms:bill-month', ['period' => $this->nextPeriod()])->expectsOutputToContain('Created 3')->assertExitCode(0);
        $this->artisan('tms:bill-month', ['period' => '2026-13'])->assertExitCode(1);
    }

    public function test_dropping_later_keeps_the_months_already_paid(): void
    {
        // Last month: register and pay.
        $this->travelTo(now()->startOfMonth()->subMonth()->addDays(3));
        $lastPeriod = now()->format('Y-m');
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $studentId = $this->studentId('student4@anjungilmu.com');
        $ids = Payment::unpaidBills($studentId)->pluck('paymentid')->implode(',');
        $this->post('/student/payments/upload', [
            'joinedIds' => $ids,
            'proofFile' => UploadedFile::fake()->createWithContent('proof.png', file_get_contents(public_path('images/tick.png'))),
        ]);
        $this->logout();
        $this->asAdmin();
        $transaction = Payment::where('paymentstatus', 'PENDING')->value('transaction_id');
        $this->post('/admin/payments/process', ['transactionId' => $transaction, 'decision' => 'approve'])->assertRedirectContains('status=approved');
        $this->logout();
        $this->travelBack();

        // This month: new bill, still unpaid, so the student may drop it themselves.
        $this->assertSame(1, Payment::generateMonthlyBills(now()->format('Y-m')));
        $this->asStudent();
        $regId = SubjectRegistration::where('studentid', $studentId)->value('subregid');
        $this->post("/student/registrations/$regId/drop")->assertRedirectContains('status=dropped');

        // Last month's tuition and the registration fee stay APPROVED (that money was really paid);
        // this month's unpaid bill is removed.
        $this->assertSame(['APPROVED'], Payment::where('subregid', $regId)->pluck('paymentstatus')->unique()->values()->all());
        $this->assertSame([$lastPeriod], Payment::where('subregid', $regId)->pluck('billingperiod')->unique()->values()->all());

        // Revenue is grouped by month AND year.
        $revenue = Payment::monthlyRevenue();
        $this->assertSame(now()->startOfMonth()->subMonth()->format('M Y'), $revenue->first()->label);
        $this->assertSame(75.0, (float) $revenue->first()->total);
    }

    public function test_dropping_in_the_month_that_was_paid_cancels_that_payment(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $studentId = $this->studentId('student4@anjungilmu.com');
        $regId = SubjectRegistration::where('studentid', $studentId)->value('subregid');
        Payment::where('subregid', $regId)->update(['paymentstatus' => 'APPROVED', 'adminid' => 'A001']);
        $this->logout();

        $this->asAdmin();
        $this->post("/admin/registrations/$regId/drop")->assertRedirectContains('status=success');

        $this->assertSame(['CANCELLED'], Payment::where('subregid', $regId)->pluck('paymentstatus')->unique()->values()->all());
    }
}
