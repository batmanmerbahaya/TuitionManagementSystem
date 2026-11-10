



<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Create New Package | Admin</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminAddPackage.css?v=updated') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .input-error { border-color:#dc2626 !important; box-shadow:0 0 0 2px rgba(220,38,38,0.15); }
        .error-msg { color:#dc2626; font-size:0.8rem; margin-top:4px; display:none; }
        .error-msg.show { display:block; }
        .code-input-wrap { display:flex; align-items:stretch; }
        .code-prefix {
            display:inline-flex; align-items:center; padding:0 14px;
            background:#e2e8f0; border:1px solid #cbd5e1; border-right:none;
            border-radius:8px 0 0 8px; font-weight:600; color:#475569; white-space:nowrap;
        }
        .code-input-wrap .input-field { border-radius:0 8px 8px 0; }
    </style>
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubjectList'])

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Create New Package</h1>
            <p class="page-subtitle">Bundle subjects together into a subscription package.</p>
        </div>

        <div style="margin-bottom: 15px; text-align: left;">
            <a href="{{ route('admin.subjects.index', ['tab' => 'packages']) }}" class="btn btn-neutral" style="text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <form action="{{ route('admin.packages.store') }}" method="POST" onsubmit="return validatePackageForm()">
            @csrf
            <div class="glass-panel">
                <div class="section-label">
                    <i class="fas fa-box-open"></i> Package Configuration
                </div>

                @if (request('msg'))
                    <div class="error-banner">
                        <i class="fas fa-exclamation-circle"></i>
                        Error: {{ request('msg') }}
                    </div>
                @endif

                <div class="form-grid">
                    <div class="form-group">
                        <label>Package Level</label>
                        <select name="level" id="levelSelect" class="input-field" required onchange="toggleSubjects()">
                            <option value="" disabled selected>-- Select Level --</option>
                            <option value="FORM 4">Form 4</option>
                            <option value="FORM 5">Form 5</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Package Code</label>
                        <div class="code-input-wrap">
                            <span class="code-prefix">PKG-</span>
                            <input type="text" name="packageCode" id="packageCodeInput" class="input-field" placeholder="e.g. SCIF4" required oninput="validatePkgCode(this)">
                        </div>
                        <span class="error-msg" id="pkgCodeError"></span>
                    </div>

                    <div class="form-group span-2">
                        <label>Package Name</label>
                        <input type="text" name="packageName" id="packageNameInput" class="input-field" placeholder="e.g. Science Stream Package" required oninput="validatePkgName(this)">
                        <span class="error-msg" id="pkgNameError"></span>
                    </div>

                    <div class="form-group">
                        <label>Total Fee (RM)</label>
                        <input type="number" name="price" class="input-field" step="0.01" placeholder="0.00" required>
                    </div>
                </div>

                <div class="subject-selection-area">
                    <div class="section-label" style="margin-top: 30px;">
                        <i class="fas fa-check-double"></i> Select Subjects
                    </div>
                    
                    <div class="subject-checklist-container">
                        <div id="list-form4" class="checklist-group">
                            @foreach ($form4Subjects as $s)
                                <div class="checklist-item">
                                    <input type="checkbox" name="selectedSubjects[]" value="{{ $s->subjectid }}" id="chk-{{ $s->subjectid }}">
                                    <label for="chk-{{ $s->subjectid }}">
                                        <span class="subject-name">{{ $s->subjectname }}</span>
                                        <span class="code-badge">{{ $s->subjectcode }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div id="list-form5" class="checklist-group">
                            @foreach ($form5Subjects as $s)
                                <div class="checklist-item">
                                    <input type="checkbox" name="selectedSubjects[]" value="{{ $s->subjectid }}" id="chk-{{ $s->subjectid }}">
                                    <label for="chk-{{ $s->subjectid }}">
                                        <span class="subject-name">{{ $s->subjectname }}</span>
                                        <span class="code-badge">{{ $s->subjectcode }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div id="list-placeholder" class="placeholder-text">
                            <i class="fas fa-info-circle"></i> Please select a level first to see available subjects.
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-neutral" onclick="window.location.href='{{ route('admin.subjects.index', ['tab' => 'packages']) }}'">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Package
                </button>
            </div>
        </form>
    </main>
	
	<div id="errorModal" class="popup-overlay" style="display: none;">
        <div class="popup-box">
            <div class="popup-icon-container error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Conflict Detected</h2>
            <p id="errorText">An issue occurred while creating the package.</p>
            <div class="popup-buttons">
                <button type="button" class="btn btn-primary" style="width: 100%;" onclick="closeErrorModal()">Understood</button>
            </div>
        </div>
    </div>
	
    <script>
        // --- CODE VALIDATION (admin types the XXXF4 part; PKG- is prepended by the system) ---
        const PKG_CODE_REGEX = /^[A-Z0-9]+F[45]$/;
        // --- NAME VALIDATION (letters, numbers and spaces only) ---
        const PKG_NAME_REGEX = /^[A-Za-z0-9 ]+$/;

        function validatePkgCode(input) {
            const err = document.getElementById('pkgCodeError');
            // Only letters and numbers allowed - strip symbols/spaces as they type.
            input.value = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const val = input.value;
            if (val.length === 0) {
                input.classList.remove('input-error');
                err.classList.remove('show'); err.textContent = '';
                return true;
            }
            if (!PKG_CODE_REGEX.test(val)) {
                input.classList.add('input-error');
                err.textContent = 'Format must be XXXF4 or XXXF5 (e.g. SCIF4).';
                err.classList.add('show');
                return false;
            }
            input.classList.remove('input-error');
            err.classList.remove('show'); err.textContent = '';
            return true;
        }

        function validatePkgName(input) {
            const err = document.getElementById('pkgNameError');
            const val = input.value.trim();
            if (val.length === 0) {
                input.classList.remove('input-error');
                err.classList.remove('show'); err.textContent = '';
                return true;
            }
            if (!PKG_NAME_REGEX.test(val)) {
                input.classList.add('input-error');
                err.textContent = 'Name can only contain letters, numbers and spaces (e.g. Basic Package Form 4).';
                err.classList.add('show');
                return false;
            }
            input.classList.remove('input-error');
            err.classList.remove('show'); err.textContent = '';
            return true;
        }

        function validatePackageForm() {
            const codeInput = document.getElementById('packageCodeInput');
            const nameInput = document.getElementById('packageNameInput');
            const codeOk = validatePkgCode(codeInput);
            const nameOk = validatePkgName(nameInput);
            if (codeInput.value.trim() === '') {
                document.getElementById('pkgCodeError').textContent = 'Package code is required.';
                document.getElementById('pkgCodeError').classList.add('show');
                codeInput.classList.add('input-error');
                return false;
            }
            return codeOk && nameOk;
        }

        function toggleSubjects() {
            const level = document.getElementById('levelSelect').value;
            document.querySelectorAll('.checklist-group').forEach(el => {
                el.classList.remove('active');
                el.querySelectorAll('input[type="checkbox"]').forEach(box => box.checked = false);
            });
            document.getElementById('list-placeholder').style.display = 'none';

            if (level === 'FORM 4') {
                document.getElementById('list-form4').classList.add('active');
            } else if (level === 'FORM 5') {
                document.getElementById('list-form5').classList.add('active');
            }
        }
    </script>
</body>
</html>