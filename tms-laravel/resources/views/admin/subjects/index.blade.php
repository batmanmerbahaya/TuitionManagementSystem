


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Subject & Package Management</title>
    
    <link rel="stylesheet" href="{{ asset('css/adminSubjectList.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .input-error { border-color:#dc2626 !important; box-shadow:0 0 0 2px rgba(220,38,38,0.15); }
        .error-msg { color:#dc2626; font-size:0.8rem; margin-top:4px; display:none; }
        .error-msg.show { display:block; }
        .code-input-wrap { display:flex; align-items:stretch; }
        .code-prefix {
            display:inline-flex; align-items:center; padding:0 12px;
            background:#e2e8f0; border:1px solid #cbd5e1; border-right:none;
            border-radius:8px 0 0 8px; font-weight:600; color:#475569; white-space:nowrap;
        }
        .code-input-wrap .detail-input { border-radius:0 8px 8px 0; }
        .code-prefix.disabled { opacity:0.6; }
    </style>
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubjectList'])

    <script>
        const allTeachers = @json($teachers);

        const form4SubjectsData = @json($form4Subjects);

        const form5SubjectsData = @json($form5Subjects);

        const form4PackagesData = @json($form4Packages);

        const form5PackagesData = @json($form5Packages);

        const allSubjectsData = {
            "FORM 4": form4SubjectsData,
            "FORM 5": form5SubjectsData
        };
    </script>

    <main class="wrapper">
        
        

        <div class="page-header">
            <h1 class="page-title">Subject & Package Management</h1>
            <p class="page-subtitle">Manage subjects, teachers, and tuition packages.</p>
        </div>

        <div class="tabs-container">
            <div class="tabs-left">
                <button id="tab-btn-subjects" class="tab-btn active" onclick="switchTab('subjects')">Subjects</button>
                <button id="tab-btn-packages" class="tab-btn" onclick="switchTab('packages')">Packages</button>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.subjects.create') }}" class="btn-add-modern"><i class="fas fa-plus"></i> New Subject</a>
                <a href="{{ route('admin.packages.create') }}" class="btn-add-modern btn-secondary"><i class="fas fa-box-open"></i> New Package</a>
            </div>
        </div>

        <div id="subjects" class="tab-content" style="display: block;">
            <div class="subject-search-bar" style="position:relative; max-width:440px; margin-bottom:24px;">
                <i class="fas fa-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                <input type="text" id="subjectSearchInput" oninput="searchSubjects()"
                       placeholder="Search subjects by name or code..."
                       style="width:100%; padding:12px 16px 12px 44px; border:1px solid #e2e8f0; border-radius:12px; font-size:0.95rem; outline:none; background:#fff; box-sizing:border-box;">
            </div>
            <div class="section-header">Form 4 Subjects</div>
            <div class="glass-panel">
                <table id="table-f4-sub">
				    <thead><tr><th>Code</th><th>Name</th><th>Schedule</th><th>Slots</th><th>Actions</th></tr></thead>
				    <tbody></tbody>
				</table>
                <div class="pagination-container" id="pagination-f4-sub"></div>
            </div>
            
            <div class="section-header" style="margin-top:40px;">Form 5 Subjects</div>
            <div class="glass-panel">
                <table id="table-f5-sub">
				    <thead><tr><th>Code</th><th>Name</th><th>Schedule</th><th>Slots</th><th>Actions</th></tr></thead>
				    <tbody></tbody>
				</table>
                <div class="pagination-container" id="pagination-f5-sub"></div>
            </div>
        </div>

        <div id="packages" class="tab-content" style="display:none;">
            <div class="section-header">Form 4 Packages</div>
            <div class="glass-panel">
                <table id="table-f4-pkg">
				    <thead><tr><th>Code</th><th>Package Name</th><th>Price (RM)</th><th>Actions</th></tr></thead>
				    <tbody></tbody>
				</table>
                <div class="pagination-container" id="pagination-f4-pkg"></div>
            </div>
            
            <div class="section-header" style="margin-top:40px;">Form 5 Packages</div>
            <div class="glass-panel">
                <table id="table-f5-pkg">
    				<thead><tr><th>Code</th><th>Package Name</th><th>Price (RM)</th><th>Actions</th></tr></thead>
				    <tbody></tbody>
				</table>	
                <div class="pagination-container" id="pagination-f5-pkg"></div>
            </div>
        </div>
    </main>

    <div id="viewPackageModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-styled">
                <div class="modal-title-text"><i class="fas fa-box-open"></i> Package Details</div>
                <button type="button" class="btn-close-x" onclick="closeModal('viewPackageModal')"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ url('admin/packages') }}" method="POST" id="editPkgForm">
                @csrf
                @method('PUT')
                <div class="modal-body-styled">
                    <input type="hidden" name="pkgID" id="modalPkgId">
                    <input type="hidden" name="packageLevel" id="modalPkgLevel">
                    
                    <div class="details-grid">
                        <div class="detail-group">
                            <label>Package Code</label>
                            <div class="code-input-wrap">
                                <span class="code-prefix">PKG-</span>
                                <input type="text" name="packageCode" id="modalPkgCode" class="detail-input" disabled required oninput="filterCodeInput(this)">
                            </div>
                            <span class="error-msg" id="modalPkgCodeError"></span>
                        </div>
                        <div class="detail-group">
                            <label>Monthly Fee (RM)</label>
                           <input type="number" name="price" id="modalPkgFee" class="detail-input" step="0.01" disabled required>
                        </div>
                        <div class="detail-group full-width">
                            <label>Package Name</label>
                            <input type="text" name="packageName" id="modalPkgName" class="detail-input" disabled required>
                        </div>
                        <div class="detail-group full-width">
                            <label>Included Subjects</label>
                            <div class="checklist-box" id="modalChecklist"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-styled">
                    <div id="pkg-view-btns" style="width:100%; display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-cancel" onclick="closeModal('viewPackageModal')">Close</button>
                        <button type="button" class="btn btn-edit" onclick="enableEditMode('pkg')"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div id="pkg-edit-btns" style="width:100%; display:none; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-neutral" onclick="closeModal('viewPackageModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="askConfirmation('pkg')">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="viewSubjectModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header-styled">
                <div class="modal-title-text"><i class="fas fa-book"></i> Subject Details</div>
                <button type="button" class="btn-close-x" onclick="closeModal('viewSubjectModal')"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ url('admin/subjects') }}" method="POST" id="editSubForm">
                @csrf
                @method('PUT')
                <div class="modal-body-styled">
                    <input type="hidden" name="id" id="modalSubId">
                    <input type="hidden" name="subjectLevel" id="modalSubLevel">
                    
                    <div class="details-grid">
                        <div class="detail-group">
                            <label>Subject Code</label>
                            <div class="code-input-wrap">
                                <span class="code-prefix">SUB-</span>
                                <input type="text" name="subjectCode" id="modalSubCode" class="detail-input" disabled required oninput="filterCodeInput(this)">
                            </div>
                            <span class="error-msg" id="modalSubCodeError"></span>
                        </div>
                        <div class="detail-group">
                            <label>Monthly Fee (RM)</label>
                            <input type="number" name="subjectFee" id="modalSubFee" class="detail-input" step="0.01" disabled required>
                        </div>
                        <div class="detail-group full-width">
                            <label>Subject Name</label>
                            <input type="text" name="subjectName" id="modalSubName" class="detail-input" disabled required>
                        </div>
                        
                        <div class="detail-group full-width">
                            <label>Assigned Teacher</label>
                            <select name="teacherId" id="modalSubTeacher" class="detail-input" disabled required>
                                <option value="" disabled selected>Select Teacher</option>
                            </select>
                        </div>

                        <div class="detail-group">
                            <label>Day</label>
                            <select name="subjectDay" id="modalSubDay" class="detail-input" disabled>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                         <div class="detail-group">
                            <label>Slots Available</label>
                            <input type="number" name="subjectAvailability" id="modalSubAvail" class="detail-input" disabled required>
                        </div>
                        <div class="detail-group">
                            <label>Start Time</label>
                            <input type="time" name="timeStart" id="modalSubStart" class="detail-input" min="{{ config('tms.day_start') }}" max="{{ config('tms.day_end') }}" disabled required>
                        </div>
                        <div class="detail-group">
                            <label>End Time</label>
                            <input type="time" name="timeEnd" id="modalSubEnd" class="detail-input" min="{{ config('tms.day_start') }}" max="{{ config('tms.day_end') }}" disabled required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-styled">
                    <div id="sub-view-btns" style="width:100%; display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn-cancel-modal" onclick="closeModal('viewSubjectModal')">Close</button>
                        <button type="button" class="btn-save-modal" onclick="enableEditMode('sub')"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div id="sub-edit-btns" style="width:100%; display:none; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn-cancel-modal" onclick="closeModal('viewSubjectModal')">Cancel</button>
                        <button type="button" class="btn-save-modal" onclick="askConfirmation('sub')">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmUpdateModal" class="modal-overlay">
        <div class="modal-box confirmation-box">
            <div class="modal-icon-circle"><i class="fas fa-question"></i></div>
            <h3 style="margin-bottom:10px;">Confirm Update</h3>
            <p style="color:#64748b; margin-bottom:20px;">Are you sure you want to save these changes?</p>
            <div class="modal-actions center">
                <button type="button" class="btn-cancel-modal" onclick="closeModal('confirmUpdateModal')">Cancel</button>
                <button type="button" class="btn-save-modal" id="btnConfirmSave">Yes, Update</button>
            </div>
        </div>
    </div>
    
    <div id="deleteConfirmModal" class="modal-overlay">
        <div class="modal-box confirmation-box">
            <div class="modal-icon-circle danger"><i class="fas fa-trash-alt"></i></div>
            <h3 style="margin-bottom:10px;">Delete Item</h3>
            <p id="deleteModalText" style="color:#64748b; margin-bottom:20px;">Are you sure?</p>
            <div class="modal-actions center">
                <button type="button" class="btn-icon btn-cancel-modal" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="button" class="btn-icon btn-delete" style="width:auto; padding:12px 24px; font-weight:600;" onclick="executeDelete()">Yes, Delete</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="modal-box confirmation-box">
            <div class="modal-icon-circle success"><i class="fas fa-check"></i></div>
            <h3 style="margin-bottom:10px;">Success!</h3>
            <p style="color:#64748b; margin-bottom:20px;">Operation completed.</p>
            <div class="modal-actions center">
                <button type="button" class="btn-save-modal" onclick="closeModal('successModal')">Okay</button>
            </div>
        </div>
    </div>
    
     <div id="errorModal" class="modal-overlay">
        <div class="modal-box confirmation-box">
            <div class="modal-icon-circle danger"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 style="margin-bottom:10px;">Error</h3>
            <p id="errorModalText" style="color:#64748b; margin-bottom:20px;">An error occurred.</p>
            <div class="modal-actions center">
                <button type="button" class="btn-cancel-modal" onclick="closeModal('errorModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Admin types only the XXXF4 part; SUB-/PKG- is prepended by the system.
        const EDIT_CODE_REGEX = /^[A-Z0-9]+F[45]$/;

        // Subject/Package code edit fields: letters and numbers only, must match XXXF4/XXXF5.
        window.filterCodeInput = function(input) {
            input.value = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const err = document.getElementById(input.id + 'Error');
            if (!err) return;
            if (input.value.length === 0 || EDIT_CODE_REGEX.test(input.value)) {
                input.classList.remove('input-error');
                err.classList.remove('show'); err.textContent = '';
            } else {
                input.classList.add('input-error');
                err.textContent = 'Format must be XXXF4 or XXXF5 (e.g. BIOF4).';
                err.classList.add('show');
            }
        };

        function initTeacherDropdown() {
            const select = document.getElementById('modalSubTeacher');
            if(!select) return;
            select.innerHTML = '<option value="" disabled selected>Select Teacher</option>';
            allTeachers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                select.appendChild(opt);
            });
        }
    
        window.switchTab = function(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            const targetDiv = document.getElementById(tabId);
            const targetBtn = document.getElementById('tab-btn-' + tabId);
            if (targetDiv) targetDiv.style.display = 'block';
            if (targetBtn) targetBtn.classList.add('active');
        };

        window.showModal = function(id) { 
            const m = document.getElementById(id);
            if(m) {
                m.style.display = 'flex';   
                setTimeout(() => m.classList.add('active'), 10);
            }
        };

        window.closeModal = function(id) { 
            const m = document.getElementById(id);
            if(m) {
                m.classList.remove('active'); 
                setTimeout(() => m.style.display = 'none', 300);
            }
        };

        window.enableEditMode = function(type) {
            const formId = (type === 'pkg') ? 'editPkgForm' : 'editSubForm';
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input:not([type="hidden"]), select');
            inputs.forEach(input => input.disabled = false);
            document.getElementById(type + '-view-btns').style.display = 'none';
            document.getElementById(type + '-edit-btns').style.display = 'flex';
        };

        function resetModalState(type) {
             const formId = (type === 'pkg') ? 'editPkgForm' : 'editSubForm';
             const form = document.getElementById(formId);
             const inputs = form.querySelectorAll('input:not([type="hidden"]), select');
             inputs.forEach(input => input.disabled = true);
             document.getElementById(type + '-view-btns').style.display = 'flex';
             document.getElementById(type + '-edit-btns').style.display = 'none';
        }

        const ITEMS_PER_PAGE = 5;
        const pagingState = {
            'f4-sub': { page: 1, data: form4SubjectsData, type: 'subject', level: 'FORM 4' },
            'f5-sub': { page: 1, data: form5SubjectsData, type: 'subject', level: 'FORM 5' },
            'f4-pkg': { page: 1, data: form4PackagesData, type: 'package', level: 'FORM 4' },
            'f5-pkg': { page: 1, data: form5PackagesData, type: 'package', level: 'FORM 5' }
        };

        // Keep the unfiltered lists so clearing the search box restores every subject.
        const originalSubjectData = {
            'f4-sub': form4SubjectsData.slice(),
            'f5-sub': form5SubjectsData.slice()
        };

        window.searchSubjects = function() {
            const q = (document.getElementById('subjectSearchInput').value || '').trim().toLowerCase();
            ['f4-sub', 'f5-sub'].forEach(key => {
                const src = originalSubjectData[key];
                pagingState[key].data = (q === '') ? src : src.filter(s =>
                    (s.name && s.name.toLowerCase().includes(q)) ||
                    (s.code && s.code.toLowerCase().includes(q)));
                pagingState[key].page = 1;
                renderTable(key);
            });
        };

        function renderTable(key) {
            const state = pagingState[key];
            const tbody = document.querySelector(`#table-${key} tbody`);
            const pagnDiv = document.querySelector(`#pagination-${key}`);
            
            if (!tbody) return;

            const start = (state.page - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const pageItems = state.data.slice(start, end);
            const totalPages = Math.ceil(state.data.length / ITEMS_PER_PAGE);

            tbody.innerHTML = '';
            if(pageItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:20px;">No records found.</td></tr>';
            } else {
                pageItems.forEach(item => {
                    let rowHtml = '';
                    const safeName = item.name.replace(/'/g, "\\'");
                    
                    const actionButtons = `
                        <div style="display:inline-flex;">
                            <button type="button" class="btn-icon btn-edit" title="View Details"
                                onclick="${state.type === 'subject' ? 
                                `openViewSubjectModal('${item.id}', '${safeName}', '${item.code}', '${item.fee}', '${item.day}', '${item.start}', '${item.end}', '${item.avail}', '${state.level}', '${item.teacherId}')` : 
                                `openViewPackageModal('${item.id}', '${safeName}', '${item.fee}', '${item.code}', '${state.level}', '${item.subjectIds}')`}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn-icon btn-delete" title="Delete"
                                onclick="${state.type === 'subject' ? 
                                `handleSubjectDelete('${item.id}', '${safeName}')` : 
                                `handlePkgDelete('${item.id}', '${safeName}')`}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;

                    if (state.type === 'subject') {
                        const badgeClass = state.level === 'FORM 4' ? 'badge-purple' : 'badge-blue';
                        const isFull = parseInt(item.registered) >= parseInt(item.avail);
                        const slotsText = `${item.registered}/${item.avail}`;
                        rowHtml = `
                            <tr>
                                <td><span class="badge ${badgeClass}">${item.code}</span></td>
                                <td style="font-weight:600;">${item.name}</td>
                                <td>${item.day} <small style="color:#64748b;">(${item.start} - ${item.end})</small></td>
                                <td>${isFull ? `<span class="slot-full">${slotsText}</span>` : slotsText}</td>
                                <td style="text-align:right;">${actionButtons}</td>
                            </tr>`;
                    } else {
                        const badgeClass = state.level === 'FORM 4' ? 'badge-purple' : 'badge-blue';
                        rowHtml = `
                            <tr>
                                <td><span class="badge ${badgeClass}">${item.code}</span></td>
                                <td style="font-weight:600;">${item.name}</td>
                                <td>RM ${item.fee}</td>
                                <td style="text-align:right;">${actionButtons}</td>
                            </tr>`;
                    }
                    tbody.insertAdjacentHTML('beforeend', rowHtml);
                });
            }

            pagnDiv.innerHTML = '';
            if (totalPages > 1) {
                pagnDiv.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage('${key}', ${state.page - 1})" ${state.page === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`);
                for(let i=1; i<=totalPages; i++) {
                    const activeClass = i === state.page ? 'active' : '';
                    pagnDiv.insertAdjacentHTML('beforeend', `<button class="page-btn ${activeClass}" onclick="changePage('${key}', ${i})">${i}</button>`);
                }
                pagnDiv.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage('${key}', ${state.page + 1})" ${state.page === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`);
            }
        }

        window.changePage = function(key, newPage) {
            pagingState[key].page = newPage;
            renderTable(key);
        };

        window.openViewSubjectModal = function(id, name, code, fee, day, start, end, avail, level, teacherId) {
            resetModalState('sub');
            document.getElementById('modalSubId').value = id;
            document.getElementById('editSubForm').action = '{{ url('admin/subjects') }}/' + encodeURIComponent(id);
            document.getElementById('modalSubName').value = name;
            // The field holds only the XXXF4 part; the SUB- prefix is shown separately.
            document.getElementById('modalSubCode').value = (code || '').replace(/^SUB-/i, '');
            clearCodeError('modalSubCode');
            document.getElementById('modalSubFee').value = fee;
            document.getElementById('modalSubDay').value = day;
            document.getElementById('modalSubStart').value = start;
            document.getElementById('modalSubEnd').value = end;
            document.getElementById('modalSubAvail').value = avail;
            document.getElementById('modalSubLevel').value = level;
            const teacherSelect = document.getElementById('modalSubTeacher');
            if(teacherSelect) teacherSelect.value = teacherId;
            showModal('viewSubjectModal');
        };

        window.openViewPackageModal = function(id, name, fee, code, level, currentCsv) {
            resetModalState('pkg');
            document.getElementById('modalPkgId').value = id;
            document.getElementById('editPkgForm').action = '{{ url('admin/packages') }}/' + encodeURIComponent(id);
            document.getElementById('modalPkgName').value = name;
            // The field holds only the XXXF4 part; the PKG- prefix is shown separately.
            document.getElementById('modalPkgCode').value = (code || '').replace(/^PKG-/i, '');
            clearCodeError('modalPkgCode');
            document.getElementById('modalPkgFee').value = fee;
            document.getElementById('modalPkgLevel').value = level;
            const container = document.getElementById('modalChecklist');
            container.innerHTML = ''; 
            const availableSubjects = allSubjectsData[level] || [];
            const selectedIds = currentCsv ? currentCsv.split(',').filter(x => x.trim() !== '') : [];
            if (availableSubjects.length === 0) {
                container.innerHTML = '<div style="color:#888; font-size:0.9rem;">No subjects found for this level.</div>';
            } else {
                availableSubjects.forEach(sub => {
                    const isChecked = selectedIds.includes(sub.id) ? 'checked' : '';
                    const itemHtml = `
                        <div class="checklist-item">
                            <input type="checkbox" name="selectedSubjects[]" value="${sub.id}" id="chk-${sub.id}" ${isChecked} disabled>
                            <label for="chk-${sub.id}"><span>${sub.name}</span><span class="badge badge-purple" style="margin-left:auto;">${sub.code}</span></label>
                        </div>`;
                    container.insertAdjacentHTML('beforeend', itemHtml);
                });
            }
            showModal('viewPackageModal');
        };

        function clearCodeError(inputId) {
            const input = document.getElementById(inputId);
            const err = document.getElementById(inputId + 'Error');
            if (input) input.classList.remove('input-error');
            if (err) { err.classList.remove('show'); err.textContent = ''; }
        }

        window.askConfirmation = function(type) {
            let formId = (type === 'pkg') ? 'editPkgForm' : 'editSubForm';
            const form = document.getElementById(formId);

            // 1. Check native HTML5 validity first (this triggers the "Please fill out this field" tooltip)
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // 2. Enforce the XXXF4/XXXF5 code format before saving
            const codeInput = document.getElementById(type === 'pkg' ? 'modalPkgCode' : 'modalSubCode');
            if (!EDIT_CODE_REGEX.test(codeInput.value)) {
                filterCodeInput(codeInput);
                codeInput.focus();
                return;
            }

            // 3. Show confirmation modal if everything is valid
            const confirmBtn = document.getElementById('btnConfirmSave');
            confirmBtn.onclick = function() { document.getElementById(formId).submit(); };
            showModal('confirmUpdateModal');
        };

        window.handlePkgDelete = function(id, name) { setupDelete(id, name, 'package'); };
        window.handleSubjectDelete = function(id, name) { setupDelete(id, name, 'subject'); };

        function setupDelete(id, name, type) {
            window.deleteId = id; window.deleteName = name; window.deleteType = type;
            const itemType = (type === 'package') ? "Package" : "Subject";
            document.getElementById('deleteModalText').innerText = "Delete " + itemType + ": " + name + "? This cannot be undone.";
            showModal('deleteConfirmModal');
        }

        window.executeDelete = function() {
            if (!window.deleteId) return;
            const base = (window.deleteType === 'package') ? '{{ url('admin/packages') }}/' : '{{ url('admin/subjects') }}/';
            submitAction(base + encodeURIComponent(window.deleteId), 'DELETE', { name: window.deleteName });
        };

        window.addEventListener('DOMContentLoaded', () => {
            initTeacherDropdown(); 
            renderTable('f4-sub'); renderTable('f5-sub'); renderTable('f4-pkg'); renderTable('f5-pkg');
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('tab') === 'packages') switchTab('packages');
            
            const status = urlParams.get('status');
            if (status) {
                if (status === 'error') {
                    const msg = urlParams.get('msg');
                    const errorText = document.getElementById('errorModalText');
                    if (msg) {
                        errorText.textContent = decodeURIComponent(msg.replace(/\+/g, ' '));
                    } else {
                        errorText.textContent = 'An error occurred during the operation.';
                    }
                    showModal('errorModal');
                } else {
                    // Show success modal for any other status (updated, deleted, etc.)
                    showModal('successModal');
                }
            }
        });
    </script>
</body>
</html>