
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Admin - Teacher List</title>
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminSubTeacher.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubTeacher'])

    <main class="wrapper">
        
        <div class="page-header">
            <div>
                <h1 class="page-title">Teacher Management</h1>
                <p class="page-subtitle">View list of teachers and manage staff details.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card filter-card active" data-filter="all" onclick="filterTeachers('all')" role="button" tabindex="0" aria-pressed="true" title="Show all teachers">
                <div class="card-content">
                    <div class="card-title">Total Teachers</div>
                    <div class="card-count">{{ $totalTeachers }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>

            <div class="card filter-card" data-filter="assigned" onclick="filterTeachers('assigned')" role="button" tabindex="0" aria-pressed="false" title="Show teachers who have subjects">
                <div class="card-content">
                    <div class="card-title">Assigned Teachers</div>
                    <div class="card-count">{{ $assignedCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-user-check"></i></div>
            </div>

            <div class="card filter-card" data-filter="unassigned" onclick="filterTeachers('unassigned')" role="button" tabindex="0" aria-pressed="false" title="Show teachers with no subjects yet">
                <div class="card-content">
                    <div class="card-title">Pending Assignment</div>
                    <div class="card-count">{{ $unassignedCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>

        <div class="header-action-container">
            <h3 class="section-heading"><i class="fas fa-list-ul"></i> Academic Staff</h3>
            <div class="action-bar">
                <a href="{{ route('admin.teachers.create') }}" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Teacher
                </a>
            </div>
        </div>

        <div class="glass-panel">
            <table id="teacherTable">
                <thead>
                    <tr>
                        <th width="30%">Teacher Details</th>
                        <th width="20%">Contact Info</th>
                        <th width="25%">Subjects Taught</th>
                        <th width="10%" style="text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody id="teacherTableBody">
                    @forelse ($teachers as $t)
                        @php
                            $hasSubjects = ! empty($t->subjects);
                            $imgSrc = $t->has_photo ? route('users.photo', $t->userid) : 'https://img.freepik.com/free-vector/woman-avatar-profile-round-icon_24640-14047.jpg';
                        @endphp
                    <tr class="data-row" data-filter="{{ $hasSubjects ? 'assigned' : 'unassigned' }}">
                        <td>
                            <div class="teacher-flex">
                                <div class="avatar-circle">
                                    <img src="{{ $imgSrc }}" alt="Profile" class="avatar-img">
                                </div>
                                <div class="info-text">
                                    <div class="info-name">{{ $t->username }}</div>
                                    <div class="info-id">ID: {{ $t->teacherid }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:14px; color:#0f172a; margin-bottom:2px;">{{ $t->usercontact }}</div>
                            <div style="font-size:12px; color:#64748b;">{{ $t->useremail }}</div>
                        </td>

                        <td>
                            <div class="grid-container">
                                @if (! $hasSubjects)
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-circle"></i> Needs Assignment
                                    </span>
                                @else
                                    @foreach ($t->subjects as $sub)
                                        @php
                                            $tagBadgeClass = 'badge-default';
                                            $tagDisplayLevel = $sub['level'];
                                            if (str_contains($sub['level'], '4')) { $tagBadgeClass = 'badge-f4'; $tagDisplayLevel = 'F4'; }
                                            elseif (str_contains($sub['level'], '5')) { $tagBadgeClass = 'badge-f5'; $tagDisplayLevel = 'F5'; }
                                        @endphp
                                    <span class="subject-tag tag-accent-{{ strtolower($tagDisplayLevel) }}">
                                        <span class="subject-tag-name">{{ $sub['name'] }}</span>
                                        <span class="badge-mini {{ $tagBadgeClass }}">{{ $tagDisplayLevel }}</span>
                                        <i class="fas fa-times subject-tag-remove" title="Remove this subject"
                                           onclick='confirmRemoveSubject(@json((string) $sub['id']), @json($sub['name']))'></i>
                                    </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>

                        <td style="text-align:center">
                            <div style="display:inline-flex; gap:8px;">
                                <button class="btn-icon" title="Edit Teacher Details" style="background:#e0e7ff; color:#4338ca;"
                                        data-user-id="{{ $t->userid }}" data-name="{{ $t->username }}"
                                        data-email="{{ $t->useremail }}" data-phone="{{ $t->usercontact }}"
                                        onclick="openEditTeacher(this.dataset)">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-icon btn-delete" title="Delete Teacher" onclick='confirmDelete(@json($t->userid), @json($t->username))'>
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyStateRow">
                        <td colspan="4" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fas fa-folder-open" style="font-size:40px; margin-bottom:10px; opacity:0.3;"></i><br>
                            No teachers found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div id="pagination" class="pagination-wrapper"></div>

    </main>

    <div id="editTeacherModal" class="modal-overlay">
        <div class="modal-box" style="text-align:left;">
            <h3 class="modal-title" style="text-align:center;">Edit Teacher</h3>
            <form id="editTeacherForm" method="post">
                @csrf
                @method('PUT')
                @php $field = 'width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px; font-family:inherit; margin:4px 0 12px;'; @endphp
                <label style="font-weight:600; font-size:14px;">Name</label>
                <input type="text" name="name" id="editTeacherName" required pattern="[A-Za-z\s'@]+" title="Letters, spaces, apostrophes and @ only" style="{{ $field }}">
                <label style="font-weight:600; font-size:14px;">Email</label>
                <input type="email" name="email" id="editTeacherEmail" required style="{{ $field }}">
                <label style="font-weight:600; font-size:14px;">Phone</label>
                <input type="tel" name="phone" id="editTeacherPhone" required placeholder="012-345 6789" style="{{ $field }}">
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editTeacherModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm" style="background:#4361ee;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-trash-alt"></i></div>
            <h3 class="modal-title">Delete Teacher</h3>
            <p id="deleteMsg">Are you sure you want to remove this teacher?</p>
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="button" class="btn-modal btn-confirm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-success"><i class="fas fa-check-circle"></i></div>
            <h3 class="modal-title">Success!</h3>
            <p id="successMsg">Operation completed successfully.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="closeModal('successModal')">Done</button>
            </div>
        </div>
    </div>

    <div id="removeSubjectModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-trash-alt"></i></div>
            <h3 class="modal-title">Remove Subject</h3>
            <p id="removeSubjectMsg">Are you sure you want to remove this subject?</p>
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('removeSubjectModal')">Cancel</button>
                <button type="button" class="btn-modal btn-confirm" id="confirmRemoveSubjectBtn">Remove</button>
            </div>
        </div>
    </div>

    <div id="errorModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="modal-title" id="errorTitle">Cannot Delete Teacher</h3>
            <p id="errorMsg">This teacher still has subjects assigned to them.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="closeModal('errorModal')">Understood</button>
            </div>
        </div>
    </div>

    <script>
        // --- 1. FILTER LOGIC (stat cards) ---
        const EMPTY_MESSAGES = {
            all: 'No teachers found.',
            assigned: 'No teachers have subjects assigned yet.',
            unassigned: 'Every teacher has at least one subject.',
        };

        function filterTeachers(type) {
            document.querySelectorAll('.filter-card').forEach(card => {
                const selected = card.dataset.filter === type;
                card.classList.toggle('active', selected);
                card.setAttribute('aria-pressed', selected);
            });

            // Rows carry data-filter="assigned" or "unassigned".
            document.querySelectorAll('#teacherTableBody tr.data-row').forEach(row => {
                row.classList.toggle('hidden', type !== 'all' && row.dataset.filter !== type);
            });

            updateNoResultsMessage(type);
            initPagination(5);
        }

        function updateNoResultsMessage(type) {
            const tbody = document.getElementById('teacherTableBody');
            const old = tbody.querySelector('.no-results-row');
            if (old) old.remove();

            const rows = tbody.querySelectorAll('tr.data-row');
            if (rows.length === 0) return;   // no teachers at all: the "No teachers found." row is already there
            if (tbody.querySelectorAll('tr.data-row:not(.hidden)').length > 0) return;

            tbody.insertAdjacentHTML('beforeend',
                '<tr class="no-results-row"><td colspan="4" style="text-align:center; padding:50px; color:#94a3b8;">' +
                '<i class="fas fa-filter" style="font-size:40px; margin-bottom:10px; opacity:0.3;"></i><br>' +
                EMPTY_MESSAGES[type] + '</td></tr>');
        }

        // --- 2. PAGINATION ---
        function initPagination(rowsPerPage) {
            const tableBody = document.getElementById('teacherTableBody');
            const paginationContainer = document.getElementById('pagination');
            if (!tableBody || !paginationContainer) return;

            // Get only rows that are NOT filtered out
            const allRows = Array.from(tableBody.querySelectorAll('tr.data-row'));
            const visibleRows = allRows.filter(r => !r.classList.contains('hidden'));

            if (visibleRows.length === 0) { 
                paginationContainer.innerHTML = ''; 
                return; 
            }

            const totalPages = Math.ceil(visibleRows.length / rowsPerPage);
            let currentPage = 1;

            function renderTable(page) {
                currentPage = page;
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // Hide all first
                allRows.forEach(row => row.style.display = 'none');

                // Show chunk of visible rows
                visibleRows.slice(start, end).forEach((row, index) => {
                    row.style.display = '';
                    row.style.animation = 'fadeInUp 0.3s ease forwards';
                });
                
                renderButtons();
            }

            function renderButtons() {
                paginationContainer.innerHTML = '';
                
                if(totalPages <= 1) return;

                // Prev
                const prev = document.createElement('button');
                prev.className = 'page-btn';
                prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prev.onclick = () => { if(currentPage > 1) renderTable(currentPage - 1); };
                if(currentPage === 1) prev.style.opacity = 0.5;
                paginationContainer.appendChild(prev);

                // Numbers
                for(let i=1; i<=totalPages; i++) {
                    const btn = document.createElement('button');
                    
                    // --- THIS WAS THE CAUSE OF THE ERROR ---
                    // We use simple string concatenation (+) here.
                    
                    btn.className = 'page-btn ' + (i === currentPage ? 'active' : '');
                    
                    btn.innerText = i;
                    btn.onclick = () => renderTable(i);
                    paginationContainer.appendChild(btn);
                }

                // Next
                const next = document.createElement('button');
                next.className = 'page-btn';
                next.innerHTML = '<i class="fas fa-chevron-right"></i>';
                next.onclick = () => { if(currentPage < totalPages) renderTable(currentPage + 1); };
                if(currentPage === totalPages) next.style.opacity = 0.5;
                paginationContainer.appendChild(next);
            }
            renderTable(1);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initPagination(5);
            // Enter / Space on a focused stat card works like a click.
            document.querySelectorAll('.filter-card').forEach(card => card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
            }));
        });

        // --- 3. MODAL UTILS ---
        function openModal(id) {
            const m = document.getElementById(id);
            if (m) {
                m.style.display = 'flex';
                setTimeout(() => m.classList.add('active'), 10);
            }
        }

        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) {
                m.classList.remove('active');
                setTimeout(() => m.style.display = 'none', 300);
            }
            if(id === 'successModal') {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function confirmDelete(userId, name) {
            document.getElementById('deleteMsg').innerHTML = `Are you sure you want to delete <strong>${name}</strong>?<br>This will remove all associated records.`;
            document.getElementById('confirmDeleteBtn').onclick = function() {
                submitAction('{{ url('admin/teachers') }}/' + encodeURIComponent(userId), 'DELETE');
            };
            openModal('deleteModal');
        }

        // --- 4. REMOVE SUBJECT FROM TEACHER ---
        function confirmRemoveSubject(subjectId, subjectName) {
            document.getElementById('removeSubjectMsg').innerHTML = `Are you sure you want to remove <strong>${subjectName}</strong>? This is not reversible, and is blocked if students are still enrolled in it.`;
            document.getElementById('confirmRemoveSubjectBtn').onclick = function() {
                submitAction('{{ url('admin/subjects') }}/' + encodeURIComponent(subjectId), 'DELETE', { from: 'teacher', name: subjectName });
            };
            openModal('removeSubjectModal');
        }

        // --- 5. NOTIFICATIONS ---
        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'success') {
                const name = params.get('name');
                document.getElementById('successMsg').innerText = name ? (name + " was deleted.") : "Operation successful.";
                openModal('successModal');
            } else if (params.get('status') === 'error' && params.get('reason') === 'hasSubjects') {
                const name = params.get('name');
                document.getElementById('errorMsg').innerText = (name || "This teacher") + " still has subjects assigned. Remove those subjects first before deleting the teacher.";
                openModal('errorModal');
            } else if (params.get('status') === 'deleted') {
                const name = params.get('name');
                document.getElementById('successMsg').innerText = name ? (name + " was removed.") : "Subject removed.";
                openModal('successModal');
            } else if (params.get('status') === 'updated') {
                const name = params.get('name');
                document.getElementById('successMsg').innerText = (name || "The teacher") + "'s details were updated.";
                openModal('successModal');
            } else if (params.get('status') === 'error') {
                const msg = params.get('msg');
                document.getElementById('errorTitle').innerText = "Not Allowed";
                document.getElementById('errorMsg').innerText = msg ? decodeURIComponent(msg.replace(/\+/g, ' ')) : "An error occurred.";
                openModal('errorModal');
            }
        });

        function openEditTeacher(data) {
            document.getElementById('editTeacherForm').action = '{{ url('admin/teachers') }}/' + encodeURIComponent(data.userId);
            document.getElementById('editTeacherName').value = data.name || '';
            document.getElementById('editTeacherEmail').value = data.email || '';
            document.getElementById('editTeacherPhone').value = data.phone || '';
            openModal('editTeacherModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>