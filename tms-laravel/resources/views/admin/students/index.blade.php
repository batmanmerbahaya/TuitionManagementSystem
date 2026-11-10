


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Admin - Student Management</title>
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminSubStudent.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminSubPayment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubStudent'])

    <main class="wrapper">
        
        <div class="page-header">
            <div>
                <h1 class="page-title">Student Management</h1>
                <p class="page-subtitle">View active students, manage enrollments, and remove records.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card filter-card active" data-filter="all" onclick="filterStudents('all')" role="button" tabindex="0" aria-pressed="true" title="Show all students">
	            <div class="card-content">
	                <div class="card-title">Total Students</div>
	                <div class="card-count">{{ $totalCount }}</div>
	            </div>
	            <div class="card-icon"><i class="fas fa-users"></i></div>
            </div>

			<div class="card filter-card" data-filter="form4" onclick="filterStudents('form4')" role="button" tabindex="0" aria-pressed="false" title="Show Form 4 students only">
	            <div class="card-content">
	                <div class="card-title">Form 4</div>
	                <div class="card-count">{{ $countF4 }}</div>
	            </div>
	            <div class="card-icon"><i class="fas fa-book-reader"></i></div>
            </div>

			<div class="card filter-card" data-filter="form5" onclick="filterStudents('form5')" role="button" tabindex="0" aria-pressed="false" title="Show Form 5 students only">
	            <div class="card-content">
	                <div class="card-title">Form 5</div>
	                <div class="card-count">{{ $countF5 }}</div>
	            </div>
	            <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
            </div>
        </div>

        <div class="header-action-container">
		    <h3 class="section-heading">
		        <i class="fas fa-list-ul"></i> Registered Students
		    </h3>
		    <div class="action-bar">
		        <a href="{{ route('admin.students.create') }}" class="btn-add">
		            <i class="fas fa-plus"></i> Add New Student
		        </a>
		    </div>
		</div>
		
        <div class="glass-panel">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th width="25%">Student Details</th>
                        <th>Contact</th>
                        <th>Level</th>
                        <th>Enrolled Subjects</th> 
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    @forelse ($students as $s)
                        @php
                            $studentPic = $s->has_photo ? route('users.photo', $s->userid) : \App\Support\Format::DEFAULT_AVATAR;
                            $lvlClass = 'badge-default';
                            $lvlText = $s->studentlevel ?? '-';
                            if (str_contains((string) $s->studentlevel, '4')) { $lvlClass = 'badge-f4'; $lvlText = 'FORM 4'; }
                            if (str_contains((string) $s->studentlevel, '5')) { $lvlClass = 'badge-f5'; $lvlText = 'FORM 5'; }
                        @endphp
                    <tr class="data-row" data-level="{{ $s->studentlevel }}">
                        <td>
                            <div class="student-flex">
                                <div class="avatar-circle">
                                    <img src="{{ $studentPic }}" class="avatar-img" alt="Profile">
                                </div>
                                <div class="info-text">
                                    <div class="info-name">{{ $s->username }}</div>
                                    <div class="info-id">ID: {{ $s->studentid }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:14px; color:#0f172a; margin-bottom:2px;">{{ $s->usercontact }}</div>
                            <div style="font-size:12px; color:#64748b;">{{ $s->useremail }}</div>
                        </td>

                        <td>
                            <span class="badge {{ $lvlClass }}">{{ $lvlText }}</span>
                        </td>

                        <td>
                            <div class="subject-grid">
                                @forelse ($s->subjects as $sub)
                                    <span class="subject-tag">{{ $sub }}</span>
                                @empty
                                    <span style="color:#cbd5e1; font-size:13px; font-style:italic;">No active subjects</span>
                                @endforelse
                            </div>
                        </td>

                        <td style="text-align:center">
                            <div style="display:inline-flex; gap:8px;">
                                <button class="btn-icon btn-edit" title="Edit Student Details"
                                        style="background:#e0e7ff; color:#4338ca;"
                                        data-user-id="{{ $s->userid }}" data-name="{{ $s->username }}" data-email="{{ $s->useremail }}"
                                        data-phone="{{ $s->usercontact }}" data-level="{{ $s->studentlevel }}"
                                        onclick="openEditStudent(this.dataset)">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon btn-edit" title="Manage Enrolled Subjects" onclick="openModal('manageModal-{{ $s->studentid }}')">
                                    <i class="fas fa-list-check"></i>
                                </button>
                                <button class="btn-icon btn-delete" title="Delete Student" onclick='confirmDelete(@json($s->userid), @json($s->username))'>
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fas fa-folder-open" style="font-size:40px; margin-bottom:10px; opacity:0.3;"></i><br>
                            No student records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="pagination" class="pagination-wrapper"></div>
    </main>

    @foreach ($students as $s)
        <div id="manageModal-{{ $s->studentid }}" class="modal-overlay">
            <div class="modal-box modal-box-wide">
                <h3 class="modal-title">{{ $s->username }}'s Enrolled Subjects</h3>
                <p class="modal-desc">Drop a single subject from this student's enrollment. This also resolves its payment line item.</p>

                <div class="subject-manage-list">
                    @forelse ($enrolments[$s->studentid] ?? [] as $row)
                        <div class="subject-manage-row">
                            <div class="subject-manage-info">
                                <span class="subject-manage-name">{{ $row->label }}</span>
                                <span class="status-pill {{ $row->pillClass }}">{{ $row->status }}</span>
                            </div>
                            <button class="btn-icon btn-delete" title="{{ $row->isPackage ? 'Drop this package' : 'Drop this subject' }}"
                                onclick='confirmDropSubject(@json($row->subRegId), @json($row->label), @json($s->username))'>
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    @empty
                        <p style="color:#94a3b8; text-align:center; padding: 10px 0;">No active subjects enrolled.</p>
                    @endforelse
                </div>

                <div>
                    <button class="btn-modal btn-cancel" onclick="closeModal('manageModal-{{ $s->studentid }}')">Close</button>
                </div>
            </div>
        </div>
    @endforeach

    <div id="editStudentModal" class="modal-overlay">
        <div class="modal-box" style="text-align:left;">
            <h3 class="modal-title" style="text-align:center;">Edit Student</h3>
            <p class="modal-desc" style="text-align:center;">The level can only change after the student's current subjects/packages are dropped.</p>
            <form id="editStudentForm" method="post">
                @csrf
                @method('PUT')
                @php $field = 'width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px; font-family:inherit; margin:4px 0 12px;'; @endphp
                <label style="font-weight:600; font-size:14px;">Name</label>
                <input type="text" name="name" id="editStudentName" required pattern="[A-Za-z\s'@]+" title="Letters, spaces, apostrophes and @ only" style="{{ $field }}">
                <label style="font-weight:600; font-size:14px;">Email</label>
                <input type="email" name="email" id="editStudentEmail" required style="{{ $field }}">
                <label style="font-weight:600; font-size:14px;">Phone</label>
                <input type="tel" name="phone" id="editStudentPhone" required placeholder="012-345 6789" style="{{ $field }}">
                <label style="font-weight:600; font-size:14px;">Level</label>
                <select name="level" id="editStudentLevel" style="{{ $field }}">
                    <option value="FORM 4">Form 4</option>
                    <option value="FORM 5">Form 5</option>
                </select>
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editStudentModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm" style="background:#4361ee;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dropSubjectModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-trash"></i></div>
            <h3 class="modal-title">Drop Subject</h3>
            <p id="dropSubjectMsg" class="modal-desc">Are you sure?</p>
            <div>
                <button class="btn-modal btn-cancel" onclick="closeModal('dropSubjectModal')">Cancel</button>
                <button id="confirmDropSubjectBtn" class="btn-modal btn-confirm">Yes, Drop</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-trash"></i></div>
            <h3 class="modal-title">Confirm Deletion</h3>
            <p id="deleteMsg" class="modal-desc">This action cannot be undone.</p>
            <div>
                <button class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button id="confirmDeleteBtn" class="btn-modal btn-confirm">Yes, Delete</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="modal-box">
            <div id="successIcon" class="modal-icon icon-success"><i class="fas fa-check-circle"></i></div>
            <h3 id="successTitle" class="modal-title">Success</h3>
            <p id="successMsg" class="modal-desc">Operation completed.</p>
            <button class="btn-modal" style="background:var(--success-bg); color:var(--success-text); width:100%" onclick="closeModal('successModal')">Okay</button>
        </div>
    </div>

    <script>
    const ROWS_PER_PAGE = 5;
    let currentPage = 1;
    let currentFilter = 'all';

    function initPagination() {
        updatePagination();
    }
    
    function updatePagination() {
        const tableBody = document.getElementById('studentTableBody');
        const paginationContainer = document.getElementById('pagination');
        
        if (!tableBody || !paginationContainer) return;
        const visibleRows = Array.from(tableBody.querySelectorAll('tr.data-row:not(.hidden)'));
        
        if (visibleRows.length === 0) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        const totalPages = Math.ceil(visibleRows.length / ROWS_PER_PAGE);
        if (currentPage > totalPages) {
            currentPage = 1;
        }

        renderTable(visibleRows, totalPages);

        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            paginationContainer.innerHTML = '';
            return;
        }

        paginationContainer.style.display = 'flex';
        renderButtons(totalPages);
    }

    
    function renderTable(visibleRows, totalPages) {
        const start = (currentPage - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;
        
        visibleRows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
                row.style.animation = 'fadeInUp 0.4s ease forwards';
                row.style.animationDelay = (index - start) * 0.05 + 's';
            } else {
                row.style.display = 'none';
            }
        });
    }

    
    function renderButtons(totalPages) {
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = '';
        
        const prev = document.createElement('button');
        prev.className = 'page-btn';
        prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prev.disabled = currentPage === 1;
        prev.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
            }
        };
        paginationContainer.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.innerText = i;
            btn.onclick = () => {
                currentPage = i;
                updatePagination();
            };
            paginationContainer.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'page-btn';
        next.innerHTML = '<i class="fas fa-chevron-right"></i>';
        next.disabled = currentPage === totalPages;
        next.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                updatePagination();
            }
        };
        paginationContainer.appendChild(next);
    }

    
    function filterStudents(filter) {
        currentFilter = filter;
        currentPage = 1;
        document.querySelectorAll('.filter-card').forEach(card => {
            const selected = card.dataset.filter === filter;
            card.classList.toggle('active', selected);
            card.setAttribute('aria-pressed', selected);
        });
        const rows = document.querySelectorAll('#studentTableBody .data-row');
  
        rows.forEach(row => {
            const level = row.getAttribute('data-level');
            
            if (filter === 'all') {
                row.classList.remove('hidden');
            } 
            else if (filter === 'form4') {
                if (level && level.includes('4')) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            } 
            else if (filter === 'form5') {
                if (level && level.includes('5')) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            }
        });
        
        updatePagination();
        
        updateNoResultsMessage();
    }

    function updateNoResultsMessage() {
        const tbody = document.getElementById('studentTableBody');
        const visibleRows = tbody.querySelectorAll('.data-row:not(.hidden)');
        
        const existingNoResults = tbody.querySelector('.no-results-row');
        if (existingNoResults) {
            existingNoResults.remove();
        }

        // No students at all: the "No student records found." row is already there.
        if (tbody.querySelectorAll('.data-row').length === 0) return;

        if (visibleRows.length === 0) {
            const message = { form4: 'No Form 4 students.', form5: 'No Form 5 students.' }[currentFilter] || 'No students found.';
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `
                <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                    <i class="fas fa-filter" style="font-size:40px; margin-bottom:10px; opacity:0.3;"></i><br>
                    ${message}
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    }

    function openEditStudent(data) {
        document.getElementById('editStudentForm').action = '{{ url('admin/students') }}/' + encodeURIComponent(data.userId);
        document.getElementById('editStudentName').value = data.name || '';
        document.getElementById('editStudentEmail').value = data.email || '';
        document.getElementById('editStudentPhone').value = data.phone || '';
        document.getElementById('editStudentLevel').value = data.level || 'FORM 4';
        openModal('editStudentModal');
    }

    function confirmDelete(userId, name) {
        document.getElementById('deleteMsg').innerHTML =
            `Are you sure you want to delete <strong>${name}</strong>?<br>This will remove all associated records.`;
        document.getElementById('confirmDeleteBtn').onclick = function() {
            submitAction('{{ url('admin/students') }}/' + encodeURIComponent(userId), 'DELETE');
        };
        openModal('deleteModal');
    }

    function confirmDropSubject(subRegId, subjectName, studentName) {
        document.getElementById('dropSubjectMsg').innerHTML =
            `Are you sure you want to drop <strong>${subjectName}</strong> from <strong>${studentName}</strong>'s enrollment?<br>This cannot be undone.`;
        document.getElementById('confirmDropSubjectBtn').onclick = function() {
            submitAction('{{ url('admin/registrations') }}/' + encodeURIComponent(subRegId) + '/drop');
        };
        openModal('dropSubjectModal');
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    function closeModal(id) { 
        const modal = document.getElementById(id);
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        filterStudents('all');
        // Enter / Space on a focused stat card works like a click.
        document.querySelectorAll('.filter-card').forEach(card => card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        }));
    });

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            const msg = urlParams.get('msg') || 'Operation completed successfully.';
            document.getElementById('successMsg').innerText = decodeURIComponent(msg.replace(/\+/g, ' '));
            openModal('successModal');
        }
        if (urlParams.get('error') === 'hasRegisteredSubjects') {
            const icon = document.getElementById('successIcon');
            icon.className = 'modal-icon icon-danger';
            icon.innerHTML = '<i class="fas fa-ban"></i>';
            document.getElementById('successTitle').innerText = 'Cannot Delete Student';
            document.getElementById('successMsg').innerText =
                'This student has registered subjects or packages and cannot be deleted, whether paid or not. Drop all of their registrations first if removal is required.';
            openModal('successModal');
        }
        if (urlParams.get('error') === 'edit') {
            const icon = document.getElementById('successIcon');
            icon.className = 'modal-icon icon-danger';
            icon.innerHTML = '<i class="fas fa-ban"></i>';
            document.getElementById('successTitle').innerText = 'Not Saved';
            document.getElementById('successMsg').innerText = urlParams.get('msg') || 'Please check the details and try again.';
            openModal('successModal');
        }
    });
</script>
</body>
</html>