


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Add Subject | Admin</title>
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/adminAddSubject.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
            <h1 class="page-title">Add New Subjects</h1>
            <p class="page-subtitle">Register new tuition subjects into the system.</p>
        </div>

        <div style="margin-bottom: 15px; text-align: left;">
            <a href="{{ route('admin.subjects.index') }}" class="btn btn-neutral" style="text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <form action="{{ route('admin.subjects.store') }}" method="POST" id="addSubjectForm" onsubmit="return validateSubjectForm()">
            @csrf
            
            <div class="glass-panel">
                <div class="section-label">
                    <i class="fas fa-list-ul"></i> Subject Details
                </div>

                <div id="subject-entries-container">
                    
                        <div class="subject-entry">
                            
                            
                            <div class="entry-grid">
                                <div class="form-group">
                                    <label>Subject Code</label>
                                    <div class="code-input-wrap">
                                        <span class="code-prefix">SUB-</span>
                                        <input type="text" name="code[]" class="input-field" placeholder="e.g. BIOF4" required oninput="validateCodeField(this)">
                                    </div>
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group span-2">
                                    <label>Subject Name</label>
                                    <input type="text" name="name[]" class="input-field" placeholder="e.g. Biology Form 4" required oninput="validateNameField(this)">
                                    <span class="error-msg"></span>
                                </div>
                                <div class="form-group">
                                    <label>Level</label>
                                    <select name="level[]" class="input-field" required>
                                        <option value="">Select...</option>
                                        <option value="FORM 4">FORM 4</option>
                                        <option value="FORM 5">FORM 5</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Fee (RM)</label>
                                    <input type="number" name="fee[]" class="input-field" step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="form-group">
                                    <label>Teacher</label>
                                    <select name="teacher[]" class="input-field" required>
                                        <option value="">Select Teacher...</option>
                                        @foreach ($teachers as $t)
                                            <option value="{{ $t->teacherid }}">{{ $t->username }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Day</label>
                                    <select name="day[]" class="input-field" required>
                                        <option value="">Select...</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Slots</label>
                                    <input type="number" name="avail[]" class="input-field" placeholder="30" required>
                                </div>

                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" name="start[]" class="input-field" step="1800" min="{{ config('tms.day_start') }}" max="{{ config('tms.day_end') }}" required>
                                </div>
                                <div class="form-group">
                                    <label>End Time</label>
                                    <input type="time" name="end[]" class="input-field" step="1800" min="{{ config('tms.day_start') }}" max="{{ config('tms.day_end') }}" required>
                                </div>
                            </div>
                        </div>
                    
                </div>

                <button type="button" class="btn-add-row" onclick="addNewEntry()">
                    <i class="fas fa-plus-circle"></i> Add Another Subject
                </button>

            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <button type="button" class="btn btn-neutral" onclick="window.location.href='{{ route('admin.subjects.index') }}'">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Subjects
                </button>
            </div>

        </form>
    </main>

    <div id="errorModal" class="popup-overlay" style="display: none;">
        <div class="popup-box">
            <div class="popup-icon-container error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2 id="errorTitle">Conflict Detected</h2>
            <p id="errorText">An issue occurred while saving.</p>
            <div class="popup-buttons">
                <button type="button" class="btn btn-primary" style="width: 100%;" onclick="closeErrorModal()">Understood</button>
            </div>
        </div>
    </div>

    <script>
        // 1. ADD NEW ENTRY LOGIC
        function addNewEntry() {
            const container = document.getElementById('subject-entries-container');
            const originalEntry = container.firstElementChild;
            const newEntry = originalEntry.cloneNode(true);
            
            // Clear inputs
            newEntry.querySelectorAll('input, select').forEach(input => input.value = '');
            newEntry.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
            newEntry.querySelectorAll('.error-msg').forEach(el => { el.textContent = ''; el.classList.remove('show'); });

            // Add remove button if not present (logic for the very first row clone)
            if (!newEntry.querySelector('.btn-remove')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-remove';
                btn.innerHTML = '<i class=\"fas fa-times\"></i>';
                btn.onclick = function() { removeEntry(this); };
                newEntry.insertBefore(btn, newEntry.firstChild);
            }

            container.appendChild(newEntry);
            
            // Scroll to new entry
            newEntry.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // 2. REMOVE ENTRY
        function removeEntry(btn) {
            const entry = btn.closest('.subject-entry');
            entry.style.opacity = '0';
            entry.style.transform = 'scale(0.9)';
            setTimeout(() => entry.remove(), 200);
        }

        // 3. CLIENT-SIDE SUBJECT CODE VALIDATION (real-time, inline - no popup)
        // Admin types only the XXXF4 part; SUB- is prepended by the system.
        const CODE_REGEX = /^[A-Z0-9]+F[45]$/;

        function validateCodeField(input) {
            const errorMsg = input.closest('.form-group').querySelector('.error-msg');
            // Only letters and numbers allowed - strip symbols/spaces as they type.
            input.value = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const val = input.value;

            if (val.length === 0) {
                input.classList.remove('input-error');
                if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }
                return true;
            }

            if (!CODE_REGEX.test(val)) {
                input.classList.add('input-error');
                if (errorMsg) { errorMsg.textContent = 'Format must be XXXF4 or XXXF5 (e.g. BIOF4).'; errorMsg.classList.add('show'); }
                return false;
            }

            input.classList.remove('input-error');
            if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }
            return true;
        }

        function validateSubjectCodes() {
            const codeInputs = document.querySelectorAll('input[name="code[]"]');
            let allValid = true;

            codeInputs.forEach(input => {
                input.value = input.value.trim().toUpperCase();
                if (!validateCodeField(input)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // CLIENT-SIDE SUBJECT NAME VALIDATION (real-time, inline - no popup)
        // Rejects symbols like !@#$%^&*(); allows letters, numbers and spaces.
        const NAME_REGEX = /^[A-Za-z0-9\s]+$/;

        function validateNameField(input) {
            const errorMsg = input.parentElement.querySelector('.error-msg');
            const val = input.value.trim();

            if (val.length === 0) {
                input.classList.remove('input-error');
                if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }
                return true;
            }

            if (!NAME_REGEX.test(val)) {
                input.classList.add('input-error');
                if (errorMsg) { errorMsg.textContent = 'Name can only contain letters, numbers and spaces (e.g. Biology Form 4).'; errorMsg.classList.add('show'); }
                return false;
            }

            input.classList.remove('input-error');
            if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }
            return true;
        }

        function validateSubjectNames() {
            const nameInputs = document.querySelectorAll('input[name="name[]"]');
            let allValid = true;

            nameInputs.forEach(input => {
                if (!validateNameField(input)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // Runs both code and name checks on submit (no short-circuit, so both
        // sets of inline errors show at once).
        function validateSubjectForm() {
            const codesValid = validateSubjectCodes();
            const namesValid = validateSubjectNames();
            return codesValid && namesValid;
        }

        function showErrorModal(title, msg) {
            document.getElementById('errorTitle').innerText = title;
            document.getElementById('errorText').innerText = msg;

            const m = document.getElementById('errorModal');
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('active'), 10);
        }

        // 4. ERROR MODAL LOGIC (server-redirected statuses)
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const msg = urlParams.get('msg');

            if (status === 'conflict') {
                showErrorModal("Conflict Detected", decodeURIComponent(msg || "Time conflict detected."));
            } else if (status === 'invalidCode') {
                showErrorModal("Invalid Subject Code", decodeURIComponent(msg || "Subject code is invalid."));
            } else if (status === 'invalidName') {
                showErrorModal("Invalid Subject Name", decodeURIComponent(msg || "Subject name is invalid."));
            } else if (status === 'duplicateCode') {
                showErrorModal("Duplicate Subject Code", decodeURIComponent(msg || "Subject code already exists."));
            }
        };

        function closeErrorModal() {
            const m = document.getElementById('errorModal');
            m.classList.remove('active');
            setTimeout(() => m.style.display = 'none', 300);
            
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    </script>
</body>
</html>