


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Admin Management</title>
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminSubAdmin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminSubAdmin'])

    <main class="wrapper">
        
        <div class="page-header">
            <div>
                <h1 class="page-title">Admin Management</h1>
                <p class="page-subtitle">Manage administrators and system access.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card filter-card active" data-filter="all" onclick="filterAdmins('all')" role="button" tabindex="0" aria-pressed="true" title="Show all admins">
            	<div class="card-content">
                    <div class="card-title">Total Admins</div>
                    <div class="card-count">{{ $totalAdmins }}</div>
                </div>
                <div class="card-icon"><i class="fa-solid fa-people-group"></i></div>
            </div>

            <div class="card filter-card" data-filter="standard" onclick="filterAdmins('standard')" role="button" tabindex="0" aria-pressed="false" title="Show standard admins only">
            	<div class="card-content">
                    <div class="card-title">Standard Admins</div>
                    <div class="card-count">{{ $normalAdmins }}</div>
                </div>
                <div class="card-icon"><i class="fa-solid fa-users-gear"></i></div>
            </div>

            <div class="card filter-card" data-filter="super" onclick="filterAdmins('super')" role="button" tabindex="0" aria-pressed="false" title="Show super admins only">
            	<div class="card-content">
                    <div class="card-title">Super Admins</div>
                    <div class="card-count">{{ $superAdmins }}</div>
                </div>
                <div class="card-icon"><i class="fa-solid fa-user-tie"></i></div>
            </div>
            
        </div>
		
		<div class="header-action-container">
            <h3 class="section-heading"><i class="fas fa-list-ul"></i> Admin List</h3>
            <div class="action-bar">
                <a href="{{ route('admin.admins.create') }}" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Admin
                </a>
            </div>
        </div>
		
        <script>
            const currentUserId = @json(session('userId'));

            @php
                $adminsData = $admins->map(fn ($a) => [
                    'id' => $a->adminid,
                    'userId' => $a->userid,
                    'name' => $a->username,
                    'email' => $a->useremail,
                    'phone' => $a->usercontact,
                    'role' => $a->adminrole === 'S' ? 'Super Admin' : 'Admin',
                    'roleClass' => $a->adminrole === 'S' ? 'role-super' : 'role-admin',
                    'roleValue' => $a->adminrole,
                ])->values();
            @endphp
            const adminsData = @json($adminsData);
        </script>

        <div class="glass-panel">
            <table id="adminTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="pagination-wrapper" id="paginationControls"></div>
        </div>
    </main>

    <div id="deleteConfirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-danger"><i class="fas fa-trash-alt"></i></div>
	            <h3 class="modal-title">Delete Admin</h3>
	            <p id="deleteMsg">Are you sure you want to remove this admin?</p>
	            
	            <div class="modal-actions">
	                <button class="btn-modal btn-cancel" onclick="closeModal('deleteConfirmModal')">Cancel</button>
	                <button class="btn-modal btn-confirm" id="btnConfirmDelete">Delete</button>
	            </div>
        </div>
    </div>

    <div id="roleConfirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon icon-warning"><i class="fas fa-user-shield"></i></div>
            <h3 class="modal-title">Change Role</h3>
            <p id="roleMsg">Change this admin's role?</p>
            <div class="modal-actions">
                <button class="btn-modal btn-cancel" onclick="closeModal('roleConfirmModal')">Cancel</button>
                <button class="btn-modal btn-confirm" id="btnConfirmRole" style="background:#4361ee;">Change Role</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="modal-box">
            <div id="successIcon" class="modal-icon icon-success"><i class="fas fa-check"></i></div>
            <h3 id="successTitle" class="modal-title">Success</h3>
            <p id="successMsg" class="modal-desc">Operation completed successfully.</p>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="closeModal('successModal')">Done</button>
            </div>
        </div>
    </div>


    <script>
        // --- FILTER (stat cards) ---
        let currentFilter = 'all';
        const EMPTY_MESSAGES = { all: 'No admins found.', standard: 'No standard admins.', super: 'No super admins.' };

        function filteredAdmins() {
            if (currentFilter === 'super') return adminsData.filter(a => a.roleValue === 'S');
            if (currentFilter === 'standard') return adminsData.filter(a => a.roleValue !== 'S');
            return adminsData;
        }

        function filterAdmins(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-card').forEach(card => {
                const selected = card.dataset.filter === filter;
                card.classList.toggle('active', selected);
                card.setAttribute('aria-pressed', selected);
            });
            currentPage = 1;
            renderTable(1);
        }

        // --- PAGINATION LOGIC ---
        const ITEMS_PER_PAGE = 5;
        let currentPage = 1;

        function renderTable(page) {
            const tbody = document.querySelector("#adminTable tbody");
            tbody.innerHTML = "";
            const list = filteredAdmins();

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">' + EMPTY_MESSAGES[currentFilter] + '</td></tr>';
                document.getElementById("paginationControls").innerHTML = "";
                return;
            }

            const start = (page - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const items = list.slice(start, end);

            items.forEach(item => {
                const safeName = item.name.replace(/'/g, "\\'");
                const roleCell = `<span class="role-badge ${item.roleClass}">${item.role}</span>`;
                const isMe = item.userId === currentUserId;
                const newRole = item.roleValue === 'S' ? 'A' : 'S';
                const actions = isMe
                    ? `<span style="color:#94a3b8; font-size:13px;">(you)</span>`
                    : `<div style="display:inline-flex; gap:8px;">
                            <button class="btn-icon btn-edit" title="${newRole === 'S' ? 'Make Super Admin' : 'Make normal Admin'}"
                                    onclick="confirmRole('${item.userId}', '${safeName}', '${newRole}')">
                                <i class="fas ${newRole === 'S' ? 'fa-user-shield' : 'fa-user-minus'}"></i>
                            </button>
                            <button class="btn-icon btn-delete" title="Remove admin" onclick="confirmDelete('${item.userId}', '${safeName}')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                       </div>`;
                const row = `
                    <tr>
                        <td style="font-weight:600; color:var(--text-main);">${item.name}</td>
                        <td>${item.email}</td>
                        <td>${item.phone}</td>
                        <td>${roleCell}</td>
                        <td style="text-align: center;">${actions}</td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });

            renderPagination();
        }

        function renderPagination() {
            const container = document.getElementById("paginationControls");
            container.innerHTML = "";
            const totalPages = Math.ceil(filteredAdmins().length / ITEMS_PER_PAGE);
            if (totalPages <= 1) return;

            const prevDisabled = (currentPage === 1) ? 'disabled' : '';
            container.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage(${currentPage - 1})" ${prevDisabled}><i class="fas fa-chevron-left"></i></button>`);

            for (let i = 1; i <= totalPages; i++) {
                const active = (i === currentPage) ? 'active' : '';
                container.insertAdjacentHTML('beforeend', `<button class="page-btn ${active}" onclick="changePage(${i})">${i}</button>`);
            }

            const nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
            container.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage(${currentPage + 1})" ${nextDisabled}><i class="fas fa-chevron-right"></i></button>`);
        }

        function changePage(newPage) {
            const totalPages = Math.ceil(filteredAdmins().length / ITEMS_PER_PAGE);
            if (newPage < 1 || newPage > totalPages) return;
            currentPage = newPage;
            renderTable(currentPage);
        }

        // --- MODAL LOGIC (FIXED) ---
        function showModal(id) {
            const m = document.getElementById(id);
            if(m) {
                m.style.display = 'flex'; // Uses flex to center
                setTimeout(() => m.classList.add('active'), 10);
            }
        }

        function closeModal(id) {
            const m = document.getElementById(id);
            if(m) {
                m.classList.remove('active');
                setTimeout(() => m.style.display = 'none', 300);
            }
            if(id === 'successModal') {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function confirmDelete(userId, name) {
            document.getElementById('deleteMsg').innerText = "Permanently remove " + name + " from the system?";
            document.getElementById('btnConfirmDelete').onclick = function() {
                submitAction('{{ url('admin/admins') }}/' + encodeURIComponent(userId), 'DELETE');
            };
            showModal('deleteConfirmModal');
        }

        function confirmRole(userId, name, newRole) {
            document.getElementById('roleMsg').innerText = newRole === 'S'
                ? "Make " + name + " a Super Admin? Super Admins can add, remove and change other admins."
                : "Change " + name + " to a normal Admin? They will lose access to admin management.";
            document.getElementById('btnConfirmRole').onclick = function() {
                submitAction('{{ url('admin/admins') }}/' + encodeURIComponent(userId) + '/role', 'PATCH', { role: newRole });
            };
            showModal('roleConfirmModal');
        }

// --- INIT ---
        window.addEventListener('DOMContentLoaded', () => {
            renderTable(1);
            // Enter / Space on a focused stat card works like a click.
            document.querySelectorAll('.filter-card').forEach(card => card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
            }));
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'success') {
                const name = params.get('name');
                const rawMsg = params.get('msg');
                const msg = name ? (name + " was removed successfully.")
                    : (rawMsg ? decodeURIComponent(rawMsg.replace(/\+/g, ' ')) : "Operation completed successfully.");
                document.getElementById('successMsg').innerText = msg;
                showModal('successModal');
            } else if (params.get('status') === 'error') {
                document.getElementById('successIcon').className = 'modal-icon icon-danger';
                document.getElementById('successIcon').innerHTML = '<i class="fas fa-ban"></i>';
                document.getElementById('successTitle').innerText = 'Not Allowed';
                document.getElementById('successMsg').innerText = params.get('msg') || 'The action could not be completed.';
                showModal('successModal');
            }
        });
    </script>
</body>
</html>