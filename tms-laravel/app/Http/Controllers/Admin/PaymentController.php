<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Payment verification (approve / reject transactions) and monthly bill generation. */
class PaymentController extends Controller
{
    public function index()
    {
        $current = Payment::currentPeriod();
        $next = now()->startOfMonth()->addMonth()->format('Y-m');

        return view('admin.payments.index', [
            'pendingList' => Payment::grouped('PENDING'),
            'historyList' => Payment::grouped('HISTORY'),
            'currentPeriod' => $current,
            'billingOptions' => [$current => Payment::periodLabel($current), $next => Payment::periodLabel($next)],
            'billedThisMonth' => Payment::where('paymenttype', 'TUITION')->where('billingperiod', $current)->count(),
        ]);
    }

    /** "Generate monthly bills" button: bills every active registration for the chosen month. */
    public function generateBills(Request $request)
    {
        $period = (string) $request->input('period', Payment::currentPeriod());
        $latestAllowed = now()->startOfMonth()->addMonth()->format('Y-m');

        if (! Payment::isValidPeriod($period) || $period > $latestAllowed) {
            return redirect()->route('admin.payments.index', ['error' => 'period']);
        }

        try {
            $count = Payment::generateMonthlyBills($period);
        } catch (\Throwable $e) {
            Log::error('Monthly billing failed: '.$e->getMessage());

            return redirect()->route('admin.payments.index', ['error' => 'failed']);
        }

        return redirect()->route('admin.payments.index', [
            'status' => 'billed',
            'count' => $count,
            'period' => Payment::periodLabel($period),
        ]);
    }

    public function process(Request $request)
    {
        $ids = (string) ($request->input('transactionId') ?: $request->input('paymentIds'));
        $decision = $request->input('decision');
        $adminId = optional(Auth::user()->admin)->adminid;

        if ($ids === '' || ! in_array($decision, ['approve', 'reject'], true) || ! $adminId) {
            return redirect()->route('admin.payments.index', ['error' => 'failed']);
        }

        try {
            Payment::process($ids, $decision === 'approve' ? 'APPROVED' : 'REJECTED', $adminId);
        } catch (\Throwable $e) {
            Log::error('Payment processing failed: '.$e->getMessage());

            return redirect()->route('admin.payments.index', ['error' => 'failed']);
        }

        return redirect()->route('admin.payments.index', ['status' => $decision === 'approve' ? 'approved' : 'rejected']);
    }
}
