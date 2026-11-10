<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Student payments: bills and history, checkout and proof upload. */
class PaymentController extends Controller
{
    private function student(): Student
    {
        return Student::where('userid', Auth::id())->firstOrFail();
    }

    public function index()
    {
        $student = $this->student();
        $pendingBills = Payment::unpaidBills($student->studentid);
        $history = Payment::grouped('HISTORY', $student->userid)->sortByDesc('paymentDate')->values();

        return view('student.payments', [
            'pendingBills' => $pendingBills,
            'paymentHistory' => $history,
            'totalOutstanding' => $pendingBills->sum(fn ($p) => (float) $p->paymentamount),
        ]);
    }

    /** "Pay Now" - summary of the selected bills, QR code and proof upload form. */
    public function checkout(Request $request)
    {
        $selected = array_map('strval', (array) $request->input('selectedInvoices', []));
        if (empty($selected)) {
            return redirect()->route('student.payments.index', ['error' => 'noselection']);
        }

        $student = $this->student();
        $checkoutList = Payment::unpaidBills($student->studentid)
            ->filter(fn ($p) => in_array(trim($p->paymentid), $selected, true))
            ->values();

        if ($checkoutList->isEmpty()) {
            return redirect()->route('student.payments.index', ['error' => 'noselection']);
        }

        return view('student.checkout', [
            'checkoutList' => $checkoutList,
            'grandTotal' => $checkoutList->sum(fn ($p) => (float) $p->paymentamount),
            'joinedIds' => $checkoutList->pluck('paymentid')->implode(','),
            'reference' => $student->userid,
        ]);
    }

    public function upload(Request $request)
    {
        $joinedIds = trim((string) $request->input('joinedIds'));
        $file = $this->uploaded($request, 'proofFile');

        if ($joinedIds === '' || $file === null) {
            return redirect()->route('student.payments.index', ['error' => 'nofile']);
        }
        if (! $file->isValid() || ! preg_match('/\.(jpg|jpeg|png|pdf)$/i', trim($file->getClientOriginalName()))) {
            return redirect()->route('student.payments.index', ['error' => 'invalidFile']);
        }

        try {
            $updated = Payment::attachProof(
                array_filter(array_map('trim', explode(',', $joinedIds))),
                file_get_contents($file->getRealPath()),
                $this->student()->studentid
            );
        } catch (\Throwable $e) {
            Log::error('Proof upload failed: '.$e->getMessage());
            $updated = 0;
        }

        return $updated > 0
            ? redirect()->route('student.payments.index', ['status' => 'paymentSuccess'])
            : redirect()->route('student.payments.index', ['error' => 'failed']);
    }
}
