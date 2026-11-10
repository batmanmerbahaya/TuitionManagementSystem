
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/studentDashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
</head>
<body>
    @include('partials.header', ['currentPage' => 'studentDashboard'])

    <main class="wrapper">
    
    	<div class="page-header">
            <h1 class="page-title">Student Dashboard</h1>
            <p class="page-subtitle">View your schedule and package list here.</p>
        </div>
    
        <section class="stats-grid">
            <a href="{{ route('student.subjects.index') }}" class="card">
                <div class="card-content">
                    <div class="card-title">Subject Registered</div>
                    <div class="card-count">{{ $subCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-book"></i></div>
            </a>
            
            <a href="{{ route('student.payments.index') }}" class="card">
                
                <div class="card-content">
                    <div class="card-title">Payment Pending</div>
                    <div class="card-count">{{ $payCount }}</div>
                </div>
                <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            </a>
        </section>

        @if (! empty($timetableGrid) && $subCount > 0)
            <section class="glass-panel">
                <div class="schedule-header"><h3><i class="fas fa-calendar-alt"></i>My Weekly Timetable</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th class="day-col"></th>
                                <th>08:00</th><th>10:00</th><th>12:00</th><th>14:00</th><th>16:00</th><th>18:00</th><th>20:00</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timetableGrid as $day => $slots)
                                <tr>
                                    <td class="day-cell">{{ $day }}</td>
                                    @foreach ($slots as $classes)
                                        <td>
                                            @foreach ($classes as $class)
                                                <div class="subject-badge badge-{{ strtolower($class['code']) }}" title="{{ $class['title'] }}">{{ $class['code'] }}</div>
                                            @endforeach
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
            </section>
        @endif

        <section class="glass-panel">
            <div class="schedule-header"><h3><i class="fas fa-box-open"></i>Packages Offered</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Package Name</th>
                        <th>Price</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $p)
                        @if (in_array((int) $p->packageid, $fullPackageIds, true))
                            <tr class="row-full" title="A subject in this package is full">
                                <td><span class="desc-sub">{{ $p->packagecode }}</span></td>
                                <td class="text-bold">{{ $p->packagename }}</td>
                                <td class="price-tag">RM {{ number_format($p->packagefee, 2) }}</td>
                                <td><span class="badge-full">FULL</span></td>
                            </tr>
                        @else
                            <tr>
                                <td><span class="desc-sub">{{ $p->packagecode }}</span></td>
                                <td class="text-bold">{{ $p->packagename }}</td>
                                <td class="price-tag">RM {{ number_format($p->packagefee, 2) }}</td>
                                <td>
                                    <a href="{{ route('student.subjects.index', ['package' => $p->packageid]) }}" class="btn btn-primary btn-small" title="See the subjects in this package">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">
                                <i class="fas fa-box-open"></i><br>No packages available for your level right now.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>