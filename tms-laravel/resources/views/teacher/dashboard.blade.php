


<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Teacher - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/teacherDashboard.css') }}">
</head>
<body>

    @include('partials.header', ['currentPage' => 'teacherDashboard'])
    
    <main class="wrapper">
    
    	<div class="page-header">
            <h1 class="page-title">Teacher Dashboard</h1>
            <p class="page-subtitle">View your schedule here.</p>
        </div>
        
        <section class="stats-grid">
            <a href="{{ route('teacher.students') }}" class="card">
                <div class="card-content">
                    <div class="card-title">Student Teaching</div>
                    <div class="card-count">{{ $studentCount ?? 0 }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
            </a>
            
            <a href="{{ route('teacher.subjects') }}" class="card">
                
                <div class="card-content">
                    <div class="card-title">Subjects Assigned</div>
                    <div class="card-count">{{ $subjectCount ?? 0 }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
            </a>
        </section>

        <section class="glass-panel">
            <div class="schedule-header">
                <h3>My Teaching Schedule</h3>
                
                <div class="level-tabs">
                    <button class="view-btn active" onclick="switchTab('form4')" id="btn-form4">Form 4</button>
                    <button class="view-btn" onclick="switchTab('form5')" id="btn-form5">Form 5</button>
                </div>
            </div>

            @foreach (['FORM 4' => 'form4', 'FORM 5' => 'form5'] as $level => $tableId)
            <div id="{{ $tableId }}-table" @if ($tableId === 'form5') style="display:none;" @endif>
                <table>
                    <thead>
                        <tr>
                            <th class="day-col">Day</th>
                            @foreach ($times as $t)<th>{{ $t }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $day)
                            <tr>
                                <td class="day-cell">{{ $day }}</td>
                                @foreach ($times as $slot => $time)
                                    <td>
                                        @foreach ($grids[$level][$day][$slot] as $class)
                                            <div class="subject-badge badge-{{ strtolower($class['code']) }}" title="{{ $class['title'] }}">{{ $class['code'] }}</div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach

        </section>
    </main>

    <script>
    function switchTab(level) {
        // Toggle Buttons
        document.getElementById('btn-form4').classList.remove('active');
        document.getElementById('btn-form5').classList.remove('active');
        document.getElementById('btn-' + level).classList.add('active');

        // Toggle Tables
        document.getElementById('form4-table').style.display = (level === 'form4') ? 'block' : 'none';
        document.getElementById('form5-table').style.display = (level === 'form5') ? 'block' : 'none';
    }
    </script>

</body>
</html>