
@if (! $p)
    <div style="text-align:center; padding:50px; color:#ef4444; font-family:'Outfit', sans-serif;">
        <h3><i class="fas fa-exclamation-triangle"></i> Receipt Unavailable</h3>
        <p>System could not find record for ID: <strong>[{{ $inputId }}]</strong></p>
        <p style="font-size:12px; color:#64748b; margin-top:10px;">Please contact support if this error persists.</p>
    </div>
@elseif (strcasecmp($p->paymentStatus, 'APPROVED') !== 0)
    <div style="text-align:center; padding:50px; color:#f59e0b; font-family:'Outfit', sans-serif;">
        <h3><i class="fas fa-clock"></i> Receipt Pending</h3>
        <p>This payment status is: <strong>{{ $p->paymentStatus }}</strong></p>
    </div>
@else
@php $total = 0; @endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Invoice #{{ $p->transactionId }}</title>
    
   	<link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/receipt.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="receipt-page">

    <div class="receipt-container" id="invoice">
        
        <div class="header">
            <div class="header-left">
                <div class="invoice-title">INVOICE</div>
                <div class="to-section">
                    <span class="label-sm">To :</span>
                    <div class="recipient-name">{{ $p->studentName }}</div>
                    <div class="recipient-details">Student ID: {{ $p->studentId }}<br>{{ $p->studentEmail }}</div>
                </div>
                <div style="margin-top: 15px; font-weight:600; font-size:13px; color:var(--navy-primary);">{{ \App\Support\Format::phone($p->studentPhone) }}</div>
            </div>

            <div class="header-right">
                <div class="logo-box">
                    <div class="company-logo-text">ANJUNG<br>ILMU</div>
                    <div class="company-slogan">Center for Excellence</div>
                </div>
                <div class="to-section">
                    <span class="label-sm">Office Address</span>
                    <div class="sender-address">
                        {{ config('tms.centre.name') }}<br>{{ config('tms.centre.address') }}
                    </div>
                    <div class="sender-address" style="margin-top:5px; font-weight:600;">{{ config('tms.centre.phone') }}</div>
                </div>
            </div>
        </div>

        <div class="dark-bar">
            <div class="bar-column">
                <span class="bar-label">Amount Paid</span>
                <span class="bar-value">RM {{ number_format($p->paymentAmount, 2) }}</span>
            </div>
            <div class="bar-column">
                <span class="bar-label">Invoice Date</span>
                <span class="bar-value">{{ \Illuminate\Support\Carbon::parse($p->paymentDate)->format('d M Y') }}</span>
            </div>
            <div class="bar-column">
                <span class="bar-label">Receipt No</span>
                <span class="bar-value">{{ $p->transactionId }}</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="45%">Item Description</th>
                        <th width="20%">Month</th>
                        <th width="15%">Status</th>
                        <th width="20%">Total</th> </tr>
                </thead>
                <tbody>
                    @foreach ($p->items as $item)
                        @php $total += $item['amount']; @endphp
                    <tr>
                        <td>
                            <span class="receipt-main">{{ $item['description'] }}</span>
                            <span class="receipt-sub">{{ $item['type'] === 'REGISTRATION' ? 'One-time registration fee' : 'Tuition fees for '.ucwords(strtolower($item['periodLabel'])) }}</span>
                        </td>
                        <td>{{ ucwords(strtolower($item['periodLabel'])) }}</td>
                        <td style="font-weight:700;">PAID</td>
                        <td>RM {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bottom-section">
            <div class="payment-info">
                <div class="payment-header">Payment Method</div>
                <div class="payment-detail">DuitNow QR / Bank Transfer<br>Status: <strong>APPROVED</strong></div>
                <div class="terms-header">Terms & Conditions.</div>
                <div class="terms-text">This receipt is computer generated and no signature is required. Fees paid are non-refundable and non-transferable.</div>
            </div>

            <div class="totals-box">
                <div class="total-row"><span>SUBTOTAL</span><span>RM {{ number_format($total, 2) }}</span></div>
                <div class="total-row"><span>Tax VAT 0%</span><span>RM 0.00</span></div>
                <div class="total-row"><span>DISCOUNT 0%</span><span>RM 0.00</span></div>
                <div class="total-row final"><span>TOTAL PAID</span><span>RM {{ number_format($total, 2) }}</span></div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-item"><i class="fas fa-map-marker-alt"></i> {{ config('tms.centre.address') }}</div>
            <div class="footer-item"><i class="fas fa-phone-alt"></i> {{ config('tms.centre.phone') }}</div>
            <div class="footer-item"><i class="fas fa-envelope"></i> {{ config('tms.centre.email') }}</div>
        </div>
    </div>
	
    <div class="action-container">
	    <button class="btn btn-primary btn-fab" onclick="downloadPDF()" title="Download PDF">
            <i class="fa-solid fa-download"></i>
        </button>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice');
            
            // Temporarily remove rounded corners and shadow for the print
            element.style.boxShadow = 'none';
            element.style.borderRadius = '0';
            
            const opt = {
                margin: 0,
                filename: 'Receipt_{{ $p->transactionId }}.pdf',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true,
                    scrollX: 0,     // Prevents viewport cutoff
                    scrollY: 0,     // Prevents viewport cutoff
                    width: 800      // Forces capture of the full 800px width
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                // Restore styles after download
                element.style.boxShadow = ''; 
                element.style.borderRadius = '';
            });
        }
    </script>
</body>
</html>
@endif