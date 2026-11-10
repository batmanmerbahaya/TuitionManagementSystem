


<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminDashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
</head>
<body>

    @include('partials.header', ['currentPage' => 'adminDashboard'])

    <main class="wrapper">
	    <div class="page-header">
	        <h1 class="page-title">Admin Dashboard</h1>
	        <p class="page-subtitle">Welcome back, <strong>{{ auth()->user()->username ?: 'Admin' }}</strong>.</p>
		</div>
        
        <section class="stats-grid">
		    <a href="{{ route('admin.students.index') }}" class="card">
		        <div class="card-content">
                    <div class="card-title">Total Students</div>
                    <div class="card-count">{{ $studentCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
		    </a>
		    
		    <a href="{{ route('admin.teachers.index') }}" class="card">
		    	<div class="card-content">
                    <div class="card-title">Total Teachers</div>
                    <div class="card-count">{{ $teacherCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
		    </a>
		    
		    @if (session('adminType') === 'S')
		    <a href="{{ route('admin.admins.index') }}" class="card">
		    	<div class="card-content">
                    <div class="card-title">System Admins</div>
                    <div class="card-count">{{ $totalAdmins }}</div>
                </div>
                <div class="card-icon"><i class="fa-solid fa-user-shield"></i></div>
			</a>
		    @endif
		</section>
        
        <div class="glass-panel chart-panel">
        	<div class="schedule-header"><h3><i class="fas fa-chart-line"></i> Revenue Trends</h3></div>
            <div style="position: relative; height: 260px;"><canvas id="revenueChart"></canvas></div>
        </div>

        <div class="glass-panel table-container">
        	<div class="schedule-header">
        	    <h3><i class="far fa-clock"></i> Master Timetable</h3>
        	    <div class="tabs-vibrant">
                    <button class="tab-btn active" onclick="switchTab('form4')" id="btn-form4">Form 4</button>
                    <button class="tab-btn" onclick="switchTab('form5')" id="btn-form5">Form 5</button>
                </div>
            </div>

            @foreach (['FORM 4' => 'form4', 'FORM 5' => 'form5'] as $level => $tableId)
            <table id="{{ $tableId }}-table" @if ($tableId === 'form5') style="display:none;" @endif>
                <thead>
                    <tr><th style="width:100px;">Day</th>@foreach ($times as $t)<th>{{ $t }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr class="data-row">
                            <td class="day-cell">{{ $day }}</td>
                            @foreach ($times as $slot => $time)
                                <td>
                                    <div class="cell-content">
                                        @foreach ($grids[$level][$day][$slot] as $class)
                                            <span class="subject-badge badge-{{ strtolower($class['code']) }}" title="{{ $class['title'] }}">{{ $class['code'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endforeach

            
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function switchTab(level) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('btn-' + level).classList.add('active');
            document.getElementById('form4-table').style.display = (level === 'form4') ? 'table' : 'none';
            document.getElementById('form5-table').style.display = (level === 'form5') ? 'table' : 'none';
        }

        const ctx = document.getElementById('revenueChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, '#7209b7');
        gradient.addColorStop(1, 'rgba(114, 9, 183, 0.1)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($revenueMonths),
                datasets: [{
                    label: 'Revenue (RM)',
                    data: @json($revenueAmounts),
                    backgroundColor: gradient,
                    borderColor: '#7209b7',
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#7209b7',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>