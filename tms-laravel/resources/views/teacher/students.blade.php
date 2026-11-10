


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Teacher - My Students</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/teacherStudent.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    @include('partials.header', ['currentPage' => 'teacherStudent'])

    <main class="wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Students</h1>
                <p class="page-subtitle">View your registered student list.</p>
            </div>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name..." onkeyup="handleSearch()">
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Total Students</div>
	                <div class="card-count">{{ $total }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-users"></i></div>
            </div>

            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Form 4 Students</div>
	                <div class="card-count">{{ $countF4 }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            </div>

            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Form 5 Students</div>
	                <div class="card-count">{{ $countF5 }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>

        <script>
    	const studentsData = @json($studentsData);
        </script>

        <div class="glass-panel">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Student Name</th>
                        <th>Current Level</th>
                        <th>Phone Number</th>
                        <th>Subjects Enrolled</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="pagination-container" id="paginationControls"></div>
        </div>

    </main>

    <script>
        const ITEMS_PER_PAGE = 5; 
        let currentPage = 1;
        let filteredData = studentsData;

        function renderTable(page) {
            const tbody = document.querySelector("#studentTable tbody");
            tbody.innerHTML = "";

            if (filteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No students found matching your search.</td></tr>';
                document.getElementById("paginationControls").innerHTML = "";
                return;
            }

            const start = (page - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const paginatedItems = filteredData.slice(start, end);

            paginatedItems.forEach((item, index) => {
                const rowNum = start + index + 1;
                
                let badgeClass = "badge-gray";
                if (item.level === 'FORM 4') badgeClass = "badge-purple";
                else if (item.level === 'FORM 5') badgeClass = "badge-blue";

                const row = `
                    <tr>
                        <td style="text-align: center; color:var(--text-muted); font-weight:600;">${rowNum}</td>
                        <td>
                            <div class="profile-cell">
                                <img src="${item.img}" alt="Profile" class="avatar-img">
                                <span class="student-name-text">${item.name}</span>
                            </div>
                        </td>
                        <td><span class="badge ${badgeClass}">${item.level}</span></td>
                        <td style="font-family:monospace; color:var(--text-muted);">${item.phone}</td>
                        <td><span class="subject-text">${item.subjects}</span></td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            renderPagination();
        }

        function renderPagination() {
            const container = document.getElementById("paginationControls");
            container.innerHTML = "";
            const totalPages = Math.ceil(filteredData.length / ITEMS_PER_PAGE);
            if (totalPages <= 1) return;

            const prevDisabled = currentPage === 1 ? "disabled" : "";
            container.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage(${currentPage - 1})" ${prevDisabled}><i class="fas fa-chevron-left"></i></button>`);

            for (let i = 1; i <= totalPages; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                container.insertAdjacentHTML('beforeend', `<button class="page-btn ${activeClass}" onclick="changePage(${i})">${i}</button>`);
            }

            const nextDisabled = currentPage === totalPages ? "disabled" : "";
            container.insertAdjacentHTML('beforeend', `<button class="page-btn" onclick="changePage(${currentPage + 1})" ${nextDisabled}><i class="fas fa-chevron-right"></i></button>`);
        }

        function changePage(newPage) {
            const totalPages = Math.ceil(filteredData.length / ITEMS_PER_PAGE);
            if (newPage < 1 || newPage > totalPages) return;
            currentPage = newPage;
            renderTable(currentPage);
        }

        function handleSearch() {
            const query = document.getElementById("searchInput").value.toLowerCase();
            filteredData = studentsData.filter(student => student.name.toLowerCase().includes(query));
            currentPage = 1; 
            renderTable(1);
        }

        window.addEventListener('DOMContentLoaded', () => { renderTable(1); });
    </script>
</body>
</html>