<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subject;
use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_public_pages(): void
    {
        foreach (['/', '/login', '/signup', '/forgot-password'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_every_admin_page_renders(): void
    {
        $this->asAdmin();
        foreach (['/admin/dashboard', '/admin/admins', '/admin/admins/create', '/admin/teachers', '/admin/teachers/create',
            '/admin/students', '/admin/students/create', '/admin/subjects', '/admin/subjects/create',
            '/admin/packages/create', '/admin/payments', '/profile', '/profile/edit', '/profile/password'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_every_teacher_page_renders(): void
    {
        $this->asTeacher();
        foreach (['/teacher/dashboard', '/teacher/students', '/teacher/subjects', '/teacher/subjects/1/students', '/profile', '/profile/edit', '/profile/password'] as $page) {
            $this->get($page)->assertOk();
        }
        // ... but not another teacher's class list
        $this->get('/teacher/subjects/2/students')->assertSee('Invalid Subject ID');
    }

    public function test_every_student_page_renders(): void
    {
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);
        $ids = Payment::unpaidBills($this->studentId('student4@anjungilmu.com'))->pluck('paymentid')->all();

        foreach (['/student/dashboard', '/student/subjects', '/student/payments', '/profile', '/profile/edit', '/profile/password',
            '/student/payments/checkout?'.http_build_query(['selectedInvoices' => $ids])] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_timetables_show_classes_that_start_between_slots(): void
    {
        Subject::create([
            'subjectcode' => 'SUB-ADDF4', 'subjectname' => 'Additional mathematics', 'subjectlevel' => 'FORM 4', 'subjectfee' => 70,
            'subjectday' => 'Friday', 'subtimestart' => '09:00', 'subtimeend' => '11:00', 'subavailability' => 10, 'teacherid' => 'T001',
        ]);

        $this->asTeacher();
        // Shown in both the 08:00 and the 10:00 column.
        $this->assertSame(2, substr_count($this->get('/teacher/dashboard')->getContent(), 'title="Additional mathematics (09:00 - 11:00)"'));

        $this->asAdmin();
        $this->assertSame(2, substr_count($this->get('/admin/dashboard')->getContent(), 'title="Additional mathematics (09:00 - 11:00)"'));
    }

    public function test_receipts_show_the_centres_real_details(): void
    {
        $this->asStudent();
        $this->register(['type' => 'subject', 'subjectIds' => [4]]);
        $studentId = $this->studentId('student4@anjungilmu.com');
        Payment::whereIn('paymentid', Payment::unpaidBills($studentId)->pluck('paymentid'))
            ->update(['paymentstatus' => 'APPROVED', 'transaction_id' => 'TXN123', 'adminid' => 'A001']);

        $this->get('/receipts/TXN123')
            ->assertOk()
            ->assertSee(config('tms.centre.address'))
            ->assertSee(config('tms.centre.phone'))
            ->assertSee('One-time registration fee')
            ->assertSee('Tuition fees for '.now()->format('F Y'))
            ->assertDontSee('123 Education Street')
            ->assertDontSee('12-345 6789');

        // Another student cannot open it.
        $this->logout();
        $this->asStudent('student5@anjungilmu.com');
        $this->get('/receipts/TXN123')->assertForbidden();
    }

    public function test_the_payment_page_uses_the_configured_qr_code(): void
    {
        $this->asStudent();
        $this->register(['type' => 'package', 'id' => 1]);
        $ids = Payment::unpaidBills($this->studentId('student4@anjungilmu.com'))->pluck('paymentid')->all();

        $this->get('/student/payments/checkout?'.http_build_query(['selectedInvoices' => $ids]))
            ->assertSee('TEST-QR-PAYLOAD');

        config(['tms.payment.duitnow_qr' => '', 'tms.payment.account_number' => '1234567890']);
        $this->get('/student/payments/checkout?'.http_build_query(['selectedInvoices' => $ids]))
            ->assertDontSee('Scan to Pay')
            ->assertSee('Account No: 1234567890');
    }
}
