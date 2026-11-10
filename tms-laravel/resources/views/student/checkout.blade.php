


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Complete Payment</title>
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/studentUploadPayment.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

    @include('partials.header', ['currentPage' => 'studentSubPayment'])

    <main class="wrapper">
        <div class="page-header">
            <h1 class="page-title">Payment Gateway</h1>
            <p class="page-subtitle">Securely transfer funds and upload your proof of payment.</p>
        </div>

        <div class="payment-split-layout">
            <div class="qr-card">
                @if (config('tms.payment.duitnow_qr'))
                <div class="scan-text">Scan to Pay</div>
                <div class="qr-box">
                    <div id="qrcode-container"></div>
                </div>
                @else
                <div class="scan-text">Transfer to</div>
                @endif
                <div class="bank-info">
                    <div class="bank-name">{{ config('tms.payment.bank_name') }}</div>
                    <span class="acc-number">{{ config('tms.payment.account_name') }}</span>
                    @if (config('tms.payment.account_number'))
                        <div style="font-size: 15px; font-weight: 600; margin-top: 5px;">Account No: {{ config('tms.payment.account_number') }}</div>
                    @endif
                    <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 5px;">Reference: {{ $reference }}</div>
                </div>
                <div class="payment-steps">
                    <div class="step"><div class="step-num">1</div><p>Login to your mobile banking app.</p></div>
                    <div class="step"><div class="step-num">2</div><p>Select <strong>"Scan & Pay"</strong>.</p></div>
                    <div class="step"><div class="step-num">3</div><p>Scan code & verify recipient.</p></div>
                </div>
            </div>

            <div class="glass-panel">
                <form id="paymentForm" action="{{ route('student.payments.upload') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="joinedIds" value="{{ $joinedIds }}">

                    <div class="section-label"><i class="fas fa-receipt"></i> Order Summary</div>
                    <div class="invoice-list">
                        @foreach ($checkoutList as $item)
                            <div class="invoice-item">
                                <div>
                                    <div class="item-desc">{{ $item->description }}{{ $item->paymenttype === 'REGISTRATION' ? '' : ' - '.$item->periodLabel }}</div>
                                    <span class="item-ref">Ref: {{ $item->paymentid }}</span>
                                </div>
                                <div class="item-price">RM {{ number_format($item->paymentamount, 2) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="total-row">
                        <span class="total-label">Total Payable</span>
                        <span class="total-value">RM {{ number_format($grandTotal, 2) }}</span>
                    </div>

                    <div class="section-label"><i class="fas fa-cloud-upload-alt"></i> Upload Receipt</div>
                    <div class="upload-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-image upload-icon" id="uploadIcon"></i>
                        <div class="upload-main-text" id="mainText">Click to upload proof</div>
                        <div class="upload-sub-text" id="subText">Supported: PDF, JPG, PNG</div>
                        <div class="file-tag" id="fileTag"></div>
                        <input type="file" name="proofFile" id="fileInput" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required hidden>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn-back" onclick="history.back()">Cancel</button>
                        <button type="button" class="btn-submit" onclick="validateAndConfirm()">
                            Confirm Payment <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <div id="confirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-confirm"><i class="fas fa-question"></i></div>
            <h3 class="modal-title">Confirm Upload?</h3>
            <p class="modal-desc">Are you sure you want to submit this payment receipt for verification?</p>
            <div class="modal-actions">
                <button class="btn btn-neutral" onclick="closeModal('confirmModal')">Cancel</button>
                <button class="btn btn-primary" id="uploadBtnReal" onclick="submitForm()">Yes, Upload</button>
            </div>
        </div>
    </div>

    <div id="notificationModal" class="modal-overlay">
        <div class="modal-box">
            <div id="notifIcon" class="modal-icon icon-success"><i class="fas fa-check"></i></div>
            <h3 id="notifTitle" class="modal-title">Upload Successful</h3>
            <p id="notifDesc" class="modal-desc">
                Your upload is successful and will be reviewed. The pending approval usually takes 24 hours.
            </p>
            <div class="modal-actions">
                <button id="notifBtn" class="btn btn-primary" onclick="goToHistory()">Okay</button>
            </div>
        </div>
    </div>
    
    <div id="loadingOverlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:30000;display:none;justify-content:center;align-items:center;">
        <div style="font-size:20px;font-weight:600;color:#4361ee;">Uploading... <i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <script>
        // --- QR GENERATION (the centre's DuitNow QR text comes from TMS_DUITNOW_QR in .env) ---
        var duitNowString = @json(config('tms.payment.duitnow_qr'));
        var container = document.getElementById("qrcode-container");
        if (container && duitNowString) {
            container.innerHTML = "";
            new QRCode(container, {
                text: duitNowString, width: 200, height: 200, colorDark : "#ED2E67", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.M
            });
        }

        // --- FILE UPLOAD UI ---
        const fileInput = document.getElementById('fileInput');
        const dropZone = document.getElementById('dropZone');
        const fileTag = document.getElementById('fileTag');
        const mainText = document.getElementById('mainText');
        const subText = document.getElementById('subText');
        const icon = document.getElementById('uploadIcon');

        const allowedFileExt = /\.(jpe?g|png|pdf)$/i;

        function handleFile(file) {
            if (!allowedFileExt.test(file.name)) {
                fileInput.value = "";
                mainText.style.display = ''; subText.style.display = '';
                fileTag.style.display = 'none';
                dropZone.classList.remove('has-file'); icon.className = 'fas fa-image upload-icon';
                showNotification('error', 'Invalid File Type', 'Only .jpg, .jpeg, .png, or .pdf files are allowed.');
                return;
            }
            mainText.style.display = 'none'; subText.style.display = 'none';
            fileTag.style.display = 'inline-block'; fileTag.innerText = file.name;
            dropZone.classList.add('has-file'); icon.className = 'fas fa-check-circle upload-icon';
        }
        fileInput.addEventListener('change', function() { if (this.files && this.files.length > 0) handleFile(this.files[0]); });
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#eff6ff'; });
        dropZone.addEventListener('drop', (e) => { e.preventDefault(); if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; handleFile(e.dataTransfer.files[0]); } });

        // --- MODAL & AJAX SUBMISSION LOGIC ---

        function showNotification(type, title, desc, onOk) {
            const iconEl = document.getElementById('notifIcon');
            iconEl.className = 'modal-icon icon-' + type;
            iconEl.innerHTML = type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-check"></i>';
            document.getElementById('notifTitle').innerText = title;
            document.getElementById('notifDesc').innerText = desc;
            document.getElementById('notifBtn').onclick = onOk || function() { closeModal('notificationModal'); };
            openModal('notificationModal');
        }

        function validateAndConfirm() {
            if (fileInput.files.length === 0) {
                showNotification('error', 'Proof of Payment Required', 'Please upload your proof of payment before submitting.');
                return;
            }
            openModal('confirmModal');
        }

        // UPDATED: Submit via AJAX to keep user on this page first
        function submitForm() {
            // 1. Close Confirm Modal
            closeModal('confirmModal');
            
            // 2. Show Loading
            document.getElementById('loadingOverlay').style.display = 'flex';

            // 3. Prepare Data
            var form = document.getElementById('paymentForm');
            var formData = new FormData(form);

            // 4. Send via FETCH (Background Request)
            fetch('{{ route('student.payments.upload') }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // The controller redirects. Fetch follows it.
                // We just check if the final URL indicates success or failure.
                // Or simply assume success if we got a 200 OK and redirected.
                
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (!response.ok || response.url.includes("error")) {
                    showNotification('error', 'Upload Failed', 'Something went wrong while uploading your receipt. Please try again.');
                } else {
                    // SUCCESS! Show the notification modal on THIS page.
                    showNotification('success', 'Upload Successful', 'Your upload is successful and will be reviewed. The pending approval usually takes 24 hours.', goToHistory);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                console.error('Error:', error);
                showNotification('error', 'Upload Error', 'An error occurred during upload. Please check your connection and try again.');
            });
        }

        function goToHistory() {
            // This happens when user clicks "Okay" in the success modal
            window.location.href = "{{ route('student.payments.index') }}";
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'flex';
            setTimeout(() => { modal.classList.add('active'); }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        }
    </script>
</body>
</html>