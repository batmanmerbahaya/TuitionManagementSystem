<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Payment;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pages used by both admins and students: receipts, the payment proof viewer and
 * the package details (JSON).
 */
class PaymentDocumentController extends Controller
{
    /** Receipt for an approved transaction (id = transaction id or payment id). */
    public function receipt(Request $request, string $id)
    {
        $payment = Payment::findGroup(trim($id));

        if ($payment && ! $this->canSee($request, $payment->userId)) {
            abort(403);
        }

        return view('payments.receipt', [
            'p' => $payment,
            'inputId' => $id,
        ]);
    }

    /** Streams the uploaded proof (image or PDF) of a payment. */
    public function proof(Request $request, string $paymentId)
    {
        $payment = Payment::with('registration.student')->find($paymentId);
        if (! $payment || ! $this->canSee($request, optional(optional($payment->registration)->student)->userid)) {
            abort(404);
        }

        $bytes = $payment->getRawOriginal('paymentproof');
        if ($bytes === null) {
            abort(404);
        }

        return response($bytes, 200, [
            'Content-Type' => (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    /** JSON list of the subjects inside a package. */
    public function packageSubjects(int $packageId)
    {
        $package = Package::with(['subjects' => fn ($q) => $q->orderBy('subjectname')])->find($packageId);

        return response()->json(($package ? $package->subjects : collect())->map(fn ($s) => [
            'id' => $s->subjectid,
            'name' => $s->subjectname,
            'day' => $s->subjectday,
            'start' => Format::hm($s->subtimestart),
            'end' => Format::hm($s->subtimeend),
        ])->values());
    }

    /** Admins can see every payment; a student only their own. */
    private function canSee(Request $request, ?string $ownerUserId): bool
    {
        return $request->session()->get('userRole') === 'ADMIN' || ($ownerUserId !== null && $ownerUserId === Auth::id());
    }
}
