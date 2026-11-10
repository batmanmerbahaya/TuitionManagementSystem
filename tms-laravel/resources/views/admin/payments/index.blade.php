

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Admin - Payment Verification</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminSubPayment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubPayment'])

    <main class="wrapper">
        <div class="page-header">
            <h1 class="page-title">Payment Verification</h1>
            <p class="page-subtitle">Manage incoming proofs and transaction history</p>
        </div>

        <div class="section-heading">
            <div class="section-icon"><i class="fas fa-calendar-plus"></i></div>
            <span>Monthly Bills</span>
        </div>

        <div class="glass-panel" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
            <div style="flex:1 1 320px; min-width:0; color:#64748b; line-height:1.6;">
                <p style="margin:0;">Bills all active students for the chosen month. No one is billed twice.</p>
                <p style="margin:0;"><strong style="color:#0f172a;">{{ $billedThisMonth }}</strong> bill(s) so far for {{ $billingOptions[$currentPeriod] }}.</p>
            </div>
            <form action="{{ route('admin.payments.generate') }}" method="post" style="display:flex; gap:10px; align-items:center;">
                @csrf
                <select name="period" style="padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px; font-family:inherit;">
                    @foreach ($billingOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" title="This month's bills are also created automatically."><i class="fas fa-file-invoice-dollar"></i> Generate Bills</button>
            </form>
        </div>

        <div class="section-heading">
            <div class="section-icon"><i class="fas fa-hourglass-half"></i></div>
            <span>Pending Approvals</span>
        </div>
        
	<div class="glass-panel">
        <table>
            <thead>
                <tr>
                    <th width="15%">Ref ID</th>
                    <th width="25%">Student</th>
                    <th width="25%">Subjects</th> <th width="15%">Amount</th>
                    <th width="10%">Proof</th>
                    <th width="10%" style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody id="pendingTableBody">
                @forelse ($pendingList as $p)
                    <tr>
                        <td><span class="trans-badge">#{{ $p->transactionId }}</span></td>
                        <td>
                            <span class="student-name">{{ $p->studentName }}</span>
                            <span class="meta-info"><i class="far fa-calendar-alt"></i> {{ $p->paymentDate }}</span>
                        </td>
                        <td>
                            <div class="subject-container">
                                @foreach ($p->items as $item)
                                    <div class="subject-line">• {{ $item['description'] }}@if ($item['type'] === 'TUITION') <span style="color:#94a3b8;">({{ $item['periodLabel'] }})</span>@endif</div>
                                @endforeach
                            </div>
                        </td>
                        <td><span class="amount-text">RM {{ number_format($p->paymentAmount, 2) }}</span></td>
                        <td>
                            @if ($p->hasProof)
                                <button type="button" class="btn-view" onclick="viewProof('{{ route('payments.proof', $p->paymentId) }}')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <form id="form_{{ $p->transactionId }}" action="{{ route('admin.payments.process') }}" method="post" class="action-row">
                                @csrf
                                <input type="hidden" name="transactionId" value="{{ $p->transactionId }}">
                                <input type="hidden" name="decision" id="action_{{ $p->transactionId }}" value="">

                                <button type="button" class="btn btn-primary" title="Approve"
                                        data-name="{{ $p->studentName }}"
                                        onclick="confirmAction('approve', '{{ $p->transactionId }}', this.getAttribute('data-name'))">
                                    <i class="fas fa-check"></i>
                                </button>

                                <button type="button" class="btn btn-danger" title="Reject"
                                        data-name="{{ $p->studentName }}"
                                        onclick="confirmAction('reject', '{{ $p->transactionId }}', this.getAttribute('data-name'))">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; padding:60px; color:#94a3b8;">
                        <i class="fas fa-check-circle" style="font-size:40px; margin-bottom:15px; opacity:0.3;"></i><br>
                        No pending payments.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="pendingPagination" class="pagination-wrapper"></div>
	</div>


        <div class="section-heading">
            <div class="section-icon"><i class="fas fa-history"></i></div>
            <span>Transaction History</span>
        </div>
	<div class="glass-panel">
        <table>
            <thead>
                <tr>
                    <th width="15%">Ref ID</th>
                    <th width="30%">Student</th>
                    <th width="20%">Amount</th>
                    <th width="20%">Status</th>
                    <th width="15%" style="text-align:right">Receipt</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                @forelse ($historyList as $h)
                    <tr data-ref-id="{{ $h->transactionId }}">
                        <td><span class="trans-badge">#{{ $h->transactionId }}</span></td>
                        <td><span class="student-name">{{ $h->studentName }}</span></td>
                        <td><span class="amount-text">RM {{ number_format($h->paymentAmount, 2) }}</span></td>
                        <td>
                            <span class="status-pill {{ $h->paymentStatus === 'APPROVED' ? 'status-approved' : ($h->paymentStatus === 'REJECTED' ? 'status-rejected' : 'status-pending') }}">
                                {{ $h->paymentStatus }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            @if ($h->paymentStatus === 'APPROVED')
                                <button class="btn-view" onclick="viewReceipt('{{ route('receipts.show', $h->transactionId) }}')">
                                    <i class="fas fa-file-invoice"></i> Receipt
                                </button>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                     <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No history records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div id="historyPagination" class="pagination-wrapper"></div>
        </div>
    </main>

    <div id="confirmModal" class="popup-overlay" style="display: none;">
        <div class="popup-box">
            <div class="popup-icon-container warning">
                <i id="confirmIcon" class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 id="confirmTitle">Confirm Action</h2>
            <p id="confirmDesc">Are you sure?</p>
            <div class="popup-buttons" style="display: flex; gap: 10px;">
                <button onclick="closeModal('confirmModal')" class="btn btn-neutral" style="flex: 1;">Cancel</button>
                <button id="btnRealConfirm" onclick="executeFormSubmission()" class="btn btn-primary" style="flex: 1;">Confirm</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="popup-overlay" style="display: none;">
        <div class="popup-box">
            <div class="popup-icon-container success">
                <i class="fas fa-check"></i>
            </div>
            <h2 id="successTitle">Success</h2>
            <p id="successDesc">Action completed.</p>
            <div class="popup-buttons">
                <button onclick="closeModal('successModal')" class="btn btn-primary" style="width: 100%;">Okay, Great</button>
            </div>
        </div>
    </div>

    <div id="viewerModal" class="popup-overlay" style="display: none;">
        <div class="popup-box" style="max-width: 800px; width: 95%; height: 85vh; padding: 20px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 1.5rem;">Proof Viewer</h2>
                <span onclick="closeModal('viewerModal')" style="cursor: pointer; font-size: 28px; color: #64748b;">&times;</span>
            </div>
            <div style="flex: 1; background: #f8fafc; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;">
                <iframe id="viewerFrame" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                <img id="viewerImage" src="" style="width: 100%; height: 100%; object-fit: contain; display: none;">
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // 2. PAGINATION & ANIMATION
        // ============================================
        function initPagination(tableId, paginationContainerId, rowsPerPage) {
            const tableBody = document.getElementById(tableId);
            const paginationContainer = document.getElementById(paginationContainerId);
            if (!tableBody || !paginationContainer) return;

            const rows = Array.from(tableBody.querySelectorAll('tr'));
            
            if (rows.length === 0 || rows[0].innerText.includes("No pending") || rows[0].innerText.includes("No history")) {
                paginationContainer.style.display = 'none';
                return;
            }

            if (rows.length <= rowsPerPage) {
                paginationContainer.style.display = 'none';
                animateRows(rows); 
                return;
            }

            const totalPages = Math.ceil(rows.length / rowsPerPage);
            let currentPage = 1;

            function renderTable(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                rows.forEach((row, index) => {
                    if (index >= start && index < end) {
                        row.style.display = '';
                        row.style.opacity = '0';
                        row.style.transform = 'translateY(10px)';
                    } else {
                        row.style.display = 'none';
                    }
                });
                animateRows(rows.slice(start, end));
                renderButtons();
            }

            function animateRows(rowList) {
                rowList.forEach((row, index) => {
                    setTimeout(() => {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, index * 80); 
                });
            }

            function renderButtons() {
                paginationContainer.innerHTML = '';
                const prev = document.createElement('button');
                prev.className = 'page-btn';
                prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prev.onclick = () => { if(currentPage > 1) { currentPage--; renderTable(currentPage); }};
                paginationContainer.appendChild(prev);

                for(let i=1; i<=totalPages; i++) {
                    const btn = document.createElement('button');
                    // Completely safe concatenation (avoids JSP crashing)
                    btn.className = "page-btn " + (i === currentPage ? "active" : "");
                    btn.innerText = i;
                    btn.onclick = () => { currentPage = i; renderTable(currentPage); };
                    paginationContainer.appendChild(btn);
                }

                const next = document.createElement('button');
                next.className = 'page-btn';
                next.innerHTML = '<i class="fas fa-chevron-right"></i>';
                next.onclick = () => { if(currentPage < totalPages) { currentPage++; renderTable(currentPage); }};
                paginationContainer.appendChild(next);
            }
            renderTable(currentPage);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initPagination('pendingTableBody', 'pendingPagination', 5);
            initPagination('historyTableBody', 'historyPagination', 5);
        });

        // ============================================
        // 3. MODAL LOGIC
        // ============================================
        let targetFormId = null;

        function confirmAction(action, transId, name) {
            window.targetFormId = 'form_' + transId;
            document.getElementById('action_' + transId).value = action;
            
            const btn = document.getElementById('btnRealConfirm');
            const title = document.getElementById('confirmTitle');
            const desc = document.getElementById('confirmDesc');
            const icon = document.getElementById('confirmIcon');
            const iconContainer = icon.parentElement;

            if (action === 'approve') {
                title.innerText = "Approve Payment?";
                desc.innerHTML = "Approve payment for <b>" + name + "</b>?";
                
                iconContainer.className = "popup-icon-container success";
                icon.className = "fas fa-check";
                btn.className = "btn btn-primary";
                btn.innerHTML = "Approve";
            } else {
                title.innerText = "Reject Payment?";
                desc.innerHTML = "Reject submission from <b>" + name + "</b>?";
                
                iconContainer.className = "popup-icon-container error";
                icon.className = "fas fa-times"; 
                btn.className = "btn btn-danger";
                btn.innerHTML = "Reject";
            }
            
            openModal('confirmModal');
        }

        function executeFormSubmission() { 
            if(window.targetFormId) document.getElementById(window.targetFormId).submit(); 
        }

        function openModal(id) {
            const m = document.getElementById(id);
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('active'), 10);
        }
        
        function closeModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('active');
            setTimeout(() => {
                m.style.display = 'none';
                if(id === 'viewerModal') {
                    document.getElementById("viewerFrame").src = "";
                    document.getElementById("viewerImage").src = "";
                }
            }, 300);
        }

        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            const status = params.get('status');
            if (status) {
                const title = document.getElementById('successTitle');
                const desc = document.getElementById('successDesc');
                if (status === 'approved') { title.innerText = "Approved"; desc.innerText = "Payment verified successfully."; }
                else if (status === 'rejected') { title.innerText = "Rejected"; desc.innerText = "Payment record rejected."; }
                else if (status === 'billed') {
                    const count = params.get('count') || '0';
                    title.innerText = "Bills Generated";
                    desc.innerText = count === '0'
                        ? "Every active registration already has a bill for " + params.get('period') + "."
                        : count + " tuition bill(s) created for " + params.get('period') + ". Students will see them under Payment.";
                }
                openModal('successModal');
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (params.get('error')) {
                const title = document.getElementById('successTitle');
                const desc = document.getElementById('successDesc');
                title.innerText = "Something Went Wrong";
                desc.innerText = params.get('error') === 'period'
                    ? "Bills can only be generated for this month or next month."
                    : "The action could not be completed. Please try again.";
                openModal('successModal');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function viewProof(url) {
            // The proof is served by the app; show it as an image, or in the frame if it is a PDF.
            const frame = document.getElementById("viewerFrame");
            const img = document.getElementById("viewerImage");
            frame.style.display = "none"; img.style.display = "none";
            img.onload = function() { img.onerror = null; img.style.display = "block"; };
            img.onerror = function() { img.onerror = null; img.removeAttribute('src'); frame.src = url; frame.style.display = "block"; };
            img.src = url;
            openModal('viewerModal');
        }
        
        function viewReceipt(url) {
            document.getElementById("viewerFrame").src = url;
            document.getElementById("viewerFrame").style.display = "block";
            document.getElementById("viewerImage").style.display = "none";
            openModal('viewerModal');
        }
    </script>
</body>
</html>