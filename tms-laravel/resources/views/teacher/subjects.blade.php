

<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teacher - My Subjects</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/teacherSubject.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    @include('partials.header', ['currentPage' => 'teacherSubject'])
	
    <main class="wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Assigned Subjects</h1>
                <p class="page-subtitle">View your class schedule and manage student enrollments.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Total Students</div>
	                <div class="card-count">{{ $totalStudents }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-users"></i></div>
            </div>
            
            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Form 4 Students</div>
	                <div class="card-count">{{ $f4Students }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            </div>
            
            <div class="card">
            	<div class="card-content">
	                <div class="card-title">Form 5 Students</div>
	                <div class="card-count">{{ $f5Students }}</div>
            	</div>
            	<div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
            </div>
        </div>

        <div class="glass-panel">
	        <div class="schedule-header">
	        <h3>Form 4 Subjects</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Subject Name</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Registered</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="f4Body">
                    @php $rows = $subjectList->filter(fn ($x) => $x->subjectlevel === 'FORM 4')->values(); @endphp
                    @forelse ($rows as $i => $sub)
                            <tr class="f4-row">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ strtoupper($sub->subjectname) }}</td>
                                <td>{{ $sub->subjectday }}</td>
                                <td>{{ \App\Support\Format::hm($sub->subtimestart) }} - {{ \App\Support\Format::hm($sub->subtimeend) }}</td>
                                <td>{{ $sub->student_count }} Students</td>
                                <td>
                                    <div style="display:flex; justify-content:center;">
                                        <button class="btn btn-edit" onclick='openModal(@json($sub->subjectid), @json($sub->subjectname))'>
                                            <i class="fas fa-users"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No Form 4 subjects assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div id="pagination-f4" class="pagination-container"></div>
        	</div>
        </div>

        <div class="glass-panel">
        	<div class="schedule-header">
        	<h3>Form 5 Subjects</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Subject Name</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Registered</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="f5Body">
                    @php $rows = $subjectList->filter(fn ($x) => $x->subjectlevel === 'FORM 5')->values(); @endphp
                    @forelse ($rows as $i => $sub)
                            <tr class="f5-row">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ strtoupper($sub->subjectname) }}</td>
                                <td>{{ $sub->subjectday }}</td>
                                <td>{{ \App\Support\Format::hm($sub->subtimestart) }} - {{ \App\Support\Format::hm($sub->subtimeend) }}</td>
                                <td>{{ $sub->student_count }} Students</td>
                                <td>
                                    <div style="display:flex; justify-content:center;">
                                        <button class="btn btn-edit" onclick='openModal(@json($sub->subjectid), @json($sub->subjectname))'>
                                            <i class="fas fa-users"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No Form 5 subjects assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div id="pagination-f5" class="pagination-container"></div>
        	</div>
        </div>
    </main>
     
    <div id="studentModal" class="modal-overlay">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="modalSubjectTitle" style="font-size:20px; font-weight:700;">STUDENT LIST</h2>
                <button onclick="closeModal()" style="background:none; border:none; cursor:pointer; font-size:20px; color:#64748b;"><i class="fas fa-times"></i></button>
            </div>
            <div style="max-height:400px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:12px;">
                <table class="styled-table" style="box-shadow:none;">
                    <thead>
                        <tr><th style="width: 50px;">No</th><th>Student Name</th><th>Phone Number</th></tr>
                    </thead>
                    <tbody id="modalBody"></tbody>
                </table>
            </div>
            <div style="text-align: center;"><button class="btn-print" onclick="printStudentList()"><i class="fas fa-print"></i> PRINT LIST</button></div>
        </div>
    </div>

    <script>
        const ITEMS_PER_PAGE = 5;
        
        const state = {
            f4: { currentPage: 1 },
            f5: { currentPage: 1 }
        };

        window.addEventListener('DOMContentLoaded', () => {
            setupPagination('f4');
            setupPagination('f5');
        });

        function setupPagination(type) {
            const rows = document.querySelectorAll('.' + type + '-row');
            if (rows.length === 0) return;
            
            renderTable(type, rows);
            renderPaginationControls(type, rows.length);
        }

        function changePage(type, newPage) {
            const rows = document.querySelectorAll('.' + type + '-row');
            const totalPages = Math.ceil(rows.length / ITEMS_PER_PAGE);

            if (newPage < 1 || newPage > totalPages) return;
            
            state[type].currentPage = newPage;
            renderTable(type, rows);
            renderPaginationControls(type, rows.length);
        }

        function renderTable(type, rows) {
            const start = (state[type].currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;

            rows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function renderPaginationControls(type, totalRows) {
            const totalPages = Math.ceil(totalRows / ITEMS_PER_PAGE);
            const container = document.getElementById('pagination-' + type);
            const currentPage = state[type].currentPage;
            
            container.innerHTML = ''; // Clear existing buttons

            if (totalPages <= 1) return; // Don't show controls if only 1 page

            // 1. Prev Button
            const prevDisabled = (currentPage === 1) ? 'disabled' : '';
            container.innerHTML += `<button class="page-btn" onclick="changePage('${type}', ${currentPage - 1})" ${prevDisabled}><i class="fas fa-chevron-left"></i></button>`;

            // 2. Numbered Buttons
            for (let i = 1; i <= totalPages; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                container.innerHTML += `<button class="page-btn ${activeClass}" onclick="changePage('${type}', ${i})">${i}</button>`;
            }

            // 3. Next Button
            const nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
            container.innerHTML += `<button class="page-btn" onclick="changePage('${type}', ${currentPage + 1})" ${nextDisabled}><i class="fas fa-chevron-right"></i></button>`;
        }

        // Modal Logic
        function openModal(id, name) {
            document.getElementById("modalSubjectTitle").innerText = name.toUpperCase() + " - STUDENT LIST";
            document.getElementById("modalBody").innerHTML = "<tr><td colspan='3' style='text-align:center; padding:20px;'>Loading...</td></tr>";
            document.getElementById("studentModal").style.display = "flex";
            
            fetch('{{ url('teacher/subjects') }}/' + encodeURIComponent(id) + '/students')
                .then(r => r.text()).then(h => document.getElementById("modalBody").innerHTML = h)
                .catch(e => document.getElementById("modalBody").innerHTML = "<tr><td colspan='3' style='color:red;'>Error.</td></tr>");
        }
        function closeModal() { document.getElementById("studentModal").style.display = "none"; }
        window.onclick = function(e) { if (e.target == document.getElementById("studentModal")) closeModal(); }
        
        function printStudentList() {
            const subjectTitle = document.getElementById('modalSubjectTitle').innerText;
            const tableBody = document.getElementById('modalBody');
            const rows = tableBody.getElementsByTagName('tr');
            
            if (rows.length === 0) {
                alert('No students to print');
                return;
            }
            
            var printContent = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' + subjectTitle + '</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; padding: 40px; }';
            printContent += '.print-header { text-align: center; border-bottom: 3px solid #7209b7; padding-bottom: 20px; margin-bottom: 30px; }';
            printContent += '.print-header h1 { font-size: 24px; color: #1e293b; margin-bottom: 5px; }';
            printContent += '.print-header h2 { font-size: 18px; color: #7209b7; margin-bottom: 10px; }';
            printContent += '.print-date { font-size: 12px; color: #64748b; }';
            printContent += 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
            printContent += 'th { background-color: #7209b7; color: white; padding: 12px; text-align: left; font-weight: 600; }';
            printContent += 'td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }';
            printContent += 'tr:nth-child(even) { background-color: #f8fafc; }';
            printContent += '.total-count { margin-top: 20px; text-align: right; font-weight: 600; }';
            printContent += '.print-footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #e2e8f0; text-align: center; font-size: 12px; color: #64748b; }';
            printContent += '</style></head><body>';
            printContent += '<div class="print-header">';
            printContent += '<h1>Tuition Management System</h1>';
            printContent += '<h2>' + subjectTitle + '</h2>';
            
            var currentDate = new Date();
            var dateString = currentDate.toLocaleDateString('en-GB', {
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            var timeString = currentDate.toLocaleTimeString('en-GB', {
                hour: '2-digit', 
                minute: '2-digit'
            });
            
            printContent += '<div class="print-date">Printed on: ' + dateString + ' at ' + timeString + '</div>';
            printContent += '</div>';
            printContent += '<table><thead><tr><th style="width: 60px;">No</th><th>Student Name</th><th>Phone Number</th></tr></thead><tbody>';
            
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                if (cells.length >= 3) {
                    printContent += '<tr><td>' + cells[0].innerText + '</td><td>' + cells[1].innerText + '</td><td>' + cells[2].innerText + '</td></tr>';
                }
            }
            
            printContent += '</tbody></table>';
            printContent += '<div class="total-count">Total Students: ' + rows.length + '</div>';
            printContent += '<div class="print-footer"><p>This is a computer-generated document. No signature is required.</p></div>';
            printContent += '<script>';
            printContent += 'window.onload = function() { window.print(); };';
            printContent += 'window.onafterprint = function() { window.close(); };';
            printContent += '<\/script>';
            printContent += '</body></html>';
            
            var printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
        }
    </script>
</body>
</html>