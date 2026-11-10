


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Student - Payments</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/studentSubPayment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/receipt.css') }}">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    @include('partials.header', ['currentPage' => 'studentSubPayment'])

    <main class="wrapper">
        <div class="page-header">
            <h1 class="page-title">Financial Hub</h1>
            <p class="page-subtitle">Manage your tuition fees and view payment history.</p>
        </div>

        <div class="stats-grid">
        
            <div class="card">
                <div class="card-content">
	                <div class="card-title">Total Outstanding</div>
	                <div class="card-count" id="cardTotalOutstanding">RM {{ number_format($totalOutstanding ?? 0, 2) }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div class="card-title">Pending Invoices</div>
                    <div class="card-count">{{ $pendingBills->count() }} <span style="font-size: 14px; font-weight: 400; color: #64748b;">Items</span></div>
                </div>
                <div class="card-icon"><i class="fas fa-file-invoice"></i></div>
            </div>
            
        </div>
		
        <form id="selectionForm" action="{{ route('student.payments.checkout') }}" method="get">
            <div class="glass-panel">
            <div class="schedule-header"><h3><i class="fas fa-bolt"></i>Action Required</h3></div>
                <table id="invoiceTable">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Select</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingBills as $item)
                            @php $rejected = $item->paymentstatus === 'REJECTED'; @endphp
                            <tr onclick="toggleRow(this)">
                                <td>{{ \Illuminate\Support\Carbon::parse($item->paymentdate)->format('d M Y') }}</td>
                                <td class="desc-cell">
                                    <div class="course-icon" data-subject="{{ $item->description }}">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="desc-text">
                                        <div class="desc-main">{{ $item->description }}</div>
                                        <div class="desc-sub">{{ $item->paymenttype === 'REGISTRATION' ? 'One-time registration fee' : 'Tuition for '.$item->periodLabel }}</div>
                                    </div>
                                </td>
                                <td><span class="badge {{ $rejected ? 'badge-failed' : 'badge-pending' }}">{{ $rejected ? 'REJECTED' : 'PENDING' }}</span></td>
                                <td class="price-tag">RM {{ number_format($item->paymentamount, 2) }}</td>
                                <td style="text-align: center;">
                                    <div class="custom-checkbox"><i class="fas fa-check"></i></div>
                                    <input type="checkbox" name="selectedInvoices[]" value="{{ $item->paymentid }}"
                                           data-amount="{{ $item->paymentamount }}" style="display: none;" class="invoice-checkbox">
                                </td>
                            </tr>
                        @endforeach
                        @if ($pendingBills->isEmpty())
                            <tr><td colspan="5" style="text-align: center; padding: 50px; color: #94a3b8;">
                                <i class="fas fa-check-circle" style="font-size: 32px; margin-bottom: 10px;"></i><br>All caught up! No pending bills.
                            </td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="floating-bar" id="payBar">
			    <div class="bar-info">
			        <span class="bar-label">Total Selected:</span>
			        <span class="bar-total" id="selectedTotal">RM 0.00</span>
			    </div>
			    <button type="submit" class="btn btn-primary btn-proceed">
			        Pay Now <i class="fas fa-arrow-right"></i>
			    </button>
			</div>
        </form>

        <div style="margin-top: 60px;">
            <div class="glass-panel">
            <div class="schedule-header"><h3><i class="fas fa-history"></i>Payment History</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction Number</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-center">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        @forelse ($paymentHistory as $hist)
                                    <tr class="history-row">
                                        <td>{{ \Illuminate\Support\Carbon::parse($hist->paymentDate)->format('d M Y') }}</td>
                                        <td class="desc-cell">
                                            <div class="desc-text">
                                                <div class="desc-sub">{{ $hist->transactionId }}</div>
                                            </div>
                                        </td>
                                        <td class="price-tag">RM {{ number_format($hist->paymentAmount, 2) }}</td>
                                        <td>
                                            <span class="badge {{ $hist->paymentStatus === 'APPROVED' ? 'badge-success' : ($hist->paymentStatus === 'REJECTED' ? 'badge-failed' : 'badge-pending') }}">
                                                {{ $hist->paymentStatus }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            @if ($hist->paymentStatus === 'APPROVED')
                                                <button type="button" class="btn btn-primary" style="padding: 6px 14px; font-size: 15px;" onclick='openReceiptModal(@json($hist->transactionId))'>
                                                    <i class="fas fa-file-invoice"></i> View
                                                </button>
                                            @else
                                                <span style="color: #94a3b8; font-size: 20px; font-weight: 500;">
                                                    <i class="fas fa-minus"></i>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No transaction history found.</td></tr>
                                @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="receiptModal" class="receipt-overlay">
        <div class="receipt-box">
            <div class="receipt-header">
                <h3><i class="fas fa-file-invoice"></i> Official Receipt</h3>
                <span class="close-receipt" onclick="closeReceiptModal()">&times;</span>
            </div>
            <iframe id="receiptFrame" class="receipt-frame" src="about:blank"></iframe>
        </div>
    </div>

    <script>
        // --- 1. SMART ICONS LOGIC ---
        function updateCourseIcons() {
            document.querySelectorAll('.course-icon').forEach(el => {
                const rawSub = el.getAttribute('data-subject') || "";
                const sub = rawSub.toUpperCase();
                let cls = 'course-default'; 
                let ico = 'fa-book-open'; 

                if (sub.includes('MATH')) { cls = 'course-math'; ico = 'fa-calculator'; }
                else if (sub.includes('BIO')) { cls = 'course-bio'; ico = 'fa-dna'; }
                else if (sub.includes('CHEM')) { cls = 'course-chem'; ico = 'fa-flask'; }
                else if (sub.includes('PHY')) { cls = 'course-physics'; ico = 'fa-atom'; }
                else if (sub.includes('ENG')) { cls = 'course-english'; ico = 'fa-comments'; }
                else if (sub.includes('HIST') || sub.includes('SEJ')) { cls = 'course-history'; ico = 'fa-landmark'; }
                
                el.innerHTML = '<i class="fas ' + ico + '"></i>'; 
                el.className = 'course-icon ' + cls;
            });
        }
        window.addEventListener('load', updateCourseIcons);

        // --- 2. ROW SELECTION & TOTAL ---
        function toggleRow(row) {
            const cb = row.querySelector('input[type="checkbox"]');
            cb.checked = !cb.checked;
            row.classList.toggle('selected-row', cb.checked);
            calcTotal();
        }
        
        function calcTotal() {
            let total = 0;
            document.querySelectorAll('input[name="selectedInvoices[]"]:checked').forEach(c => total += parseFloat(c.getAttribute('data-amount') || 0));
            document.getElementById('selectedTotal').innerText = "RM " + total.toFixed(2);
            document.getElementById('payBar').classList.toggle('active', total > 0);
        }

        function openReceiptModal(paymentId) {
            const modal = document.getElementById('receiptModal');
            const frame = document.getElementById('receiptFrame');
            
            frame.src = "about:blank";
            const cacheBuster = new Date().getTime();
            setTimeout(() => {
                frame.src = "{{ url('receipts') }}/" + encodeURIComponent(paymentId) + "?_t=" + cacheBuster;
            }, 50);

            modal.style.display = 'flex';
            setTimeout(() => { modal.classList.add('active'); }, 10);
            document.body.style.overflow = 'hidden'; 
        }

        function closeReceiptModal() {
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('active');
            
            setTimeout(() => { 
                modal.style.display = 'none'; 
                document.getElementById("receiptFrame").src = "about:blank"; 
                document.body.style.overflow = 'auto';
            }, 300);
        }

        window.onclick = function(event) {
            const modal = document.getElementById("receiptModal");
            if (event.target == modal) closeReceiptModal();
        }
    </script>
    
    <div id="notificationModal" class="modal-overlay">
        <div class="modal-box">
            <div id="notifIcon" class="modal-icon icon-success">
                <i class="fas fa-check"></i>
            </div>
            <h3 id="notifTitle" class="modal-title">Upload Successful</h3>
            <p id="notifDesc" class="modal-desc">
                Your receipt has been submitted for review. Pending approval usually takes 24 hours.
            </p>
            <div class="modal-actions">
                <button class="btn-modal btn-confirm-action" onclick="closeModal('notificationModal')">Okay, Got it</button>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'success') {
                const modal = document.getElementById('notificationModal');
                
                modal.style.display = 'flex';
                setTimeout(() => { modal.classList.add('active'); }, 10);
                
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        };

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        }
    </script>
</body>
</html>