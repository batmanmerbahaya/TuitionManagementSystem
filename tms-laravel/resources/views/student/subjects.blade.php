@php $processedIds = []; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <title>Student - Registered Subject</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/studentSubSubject.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });</script>
</head>
<body>

    @include('partials.header', ['currentPage' => 'studentSubSubject'])

    <main class="wrapper">
        <div class="page-header">
            <div>
                <h2 class="page-title">Registered Subjects</h2>
                <p class="page-subtitle">View your active subjects and manage new enrollments. Unpaid subjects can be dropped directly; once paid, contact admin to drop them.</p>
            </div>
        </div>

        <div class="glass-panel">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">Level</th>
                            <th width="25%">Subject</th>
                            <th width="10%">Day</th>
                            <th width="10%">Start</th>
                            <th width="10%">End</th>
                            <th width="25%" class="text-center">Package</th>
                            <th width="10%" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($registeredItems->isEmpty())
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; display:block; margin-bottom: 10px; color:#cbd5e1;"></i>
                                    You have not registered any subjects yet.
                                </td>
                            </tr>
                        @else
                            @foreach ($registeredItems as $item)
                                @php
                                    $regId = $item->subregid;
                                    $pkgName = $item->packagename;
                                    $isNewGroup = ! in_array($regId, $processedIds, true);
                                    $processedIds[] = $regId;
                                    $canDrop = $dropEligibility[$regId] ?? false;
                                @endphp
                            <tr>
                                <td>{{ $item->subjectlevel }}</td>
                                <td>{{ $item->subjectname }}</td>
                                <td>{{ $item->subjectday }}</td>

                                <td>{{ \App\Support\Format::hm($item->subtimestart) }}</td>
                                <td>{{ \App\Support\Format::hm($item->subtimeend) }}</td>

                                @if ($pkgName !== '-')
                                    @if ($isNewGroup)
                                        <td rowspan="{{ $rowCounts[$regId] }}" class="text-center text-bold">
                                            {{ $pkgName }}
                                        </td>
                                        <td rowspan="{{ $rowCounts[$regId] }}" class="text-center">
                                            @if ($canDrop)
                                                <i class="fas fa-trash-alt" style="color:#ef4444; cursor:pointer;" title="Drop this package" onclick='confirmDropSubject(@json($regId), @json($pkgName))'></i>
                                            @else
                                                <i class="fas fa-headset" style="color:#94a3b8;" title="Contact admin to drop a subject"></i>
                                            @endif
                                        </td>
                                    @endif
                                @else
                                    <td class="text-center"><i class="fas fa-minus"></i></td>
                                    <td class="text-center">
                                        @if ($canDrop)
                                            <i class="fas fa-trash-alt" style="color:#ef4444; cursor:pointer;" title="Drop this subject" onclick='confirmDropSubject(@json($regId), @json($item->subjectname))'></i>
                                        @else
                                            <i class="fas fa-headset" style="color:#94a3b8;" title="Contact admin to drop a subject"></i>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tabs-container" style="margin-top: 40px;">
            <button class="tab-btn active" data-tab="package">Available Packages</button>
            <button class="tab-btn" data-tab="subject">Available Subjects</button>
        </div>

        <div id="package" class="tab-content active">
            <div class="glass-panel">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Package Name</th>
                                <th>Price</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($availPackages as $p)
                                @if (in_array((int) $p->packageid, $fullPackageIds, true))
                                <tr class="row-full" title="A subject in this package is full">
                                    <td><span class="desc-sub">{{ $p->packagecode }}</span></td>
                                    <td class="text-bold">{{ $p->packagename }}</td>
                                    <td class="price-tag">RM {{ number_format($p->packagefee, 2) }}</td>
                                    <td class="text-center"><span class="badge badge-full">FULL</span></td>
                                </tr>
                                @else
                                <tr id="pkg-row-{{ $p->packageid }}"
                                    onclick='selectPackageRow(@json((string) $p->packageid), @json($p->packagecode), @json($p->packagename), @json((string) $p->packagefee))'
                                    style="cursor: pointer;">
                                    <td><span class="desc-sub">{{ $p->packagecode }}</span></td>
                                    <td class="text-bold">{{ $p->packagename }}</td>
                                    <td class="price-tag">RM {{ number_format($p->packagefee, 2) }}</td>
                                    <td class="text-center"><div class="check-indicator"><i class="fas fa-eye"></i></div></td>
                                </tr>
                                @endif
                            @empty
                                <tr><td colspan="4" class="text-center" style="padding: 40px; color: #94a3b8;">No packages available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div id="subject" class="tab-content">
            <div class="glass-panel">
                <form id="subjectForm">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Subject</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th class="text-center">Slots</th>
                                    <th>Price</th>
                                    <th class="text-center">Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($availSubjects as $s)
                                    @php $isFull = (int) $s->registered_count >= (int) $s->subavailability; @endphp
                                    @if ($isFull)
                                    <tr class="row-full" title="No slots available">
                                    @else
                                    <tr onclick="toggleSubjectRow(this)" style="cursor: pointer;">
                                    @endif
                                        <td>{{ $s->subjectlevel }}</td>
                                        <td class="text-bold">{{ $s->subjectname }}</td>
                                        <td><span class="badge badge-pending">{{ $s->subjectday }}</span></td>
                                        <td>{{ \App\Support\Format::hm($s->subtimestart) }}</td>
                                        <td class="text-center">
                                            @if ($isFull)
                                                <span class="badge badge-full">FULL</span>
                                            @else
                                                <span class="badge badge-slots">{{ $s->registered_count }}/{{ $s->subavailability }}</span>
                                            @endif
                                        </td>
                                        <td class="price-tag">RM {{ number_format($s->subjectfee, 2) }}</td>

                                        <td class="text-center">
                                            @if ($isFull)
                                                <i class="fas fa-ban" style="color:#cbd5e1;" title="No slots available"></i>
                                            @else
                                                <input type="checkbox" name="subjectIds[]"
                                                       value="{{ $s->subjectid }}"
                                                       data-price="{{ $s->subjectfee }}"
                                                       class="hidden-checkbox">
                                                <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center" style="padding: 40px; color: #94a3b8;">No individual subjects available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <div class="page-content-wrap" id="details-view-wrapper" style="display: none;">
            <div class="package-details-inner">
                <div class="details-header">
                    <h2 class="details-title">Package Summary</h2>
                    <p class="details-subtitle">Review the subjects included in this bundle</p>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Package Code</span>
                        <span id="mCode" class="info-value desc-sub">...</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Package Name</span>
                        <span id="mName" class="info-value text-bold">...</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Fee</span>
                        <span id="mPrice" class="info-value price-tag">RM 0.00</span>
                    </div>
                </div>
            
                <div class="subjects-section">
                    <span class="section-label"><i class="fas fa-layer-group"></i> Included Subjects</span>
                    <div class="table-responsive details-table-wrapper">
                        <table class="details-table">
                            <thead>
                                <tr>
                                    <th width="15%" class="text-center">ID</th>
                                    <th width="35%" class="text-left">Subject</th>
                                    <th width="15%" class="text-center">Day</th>
                                    <th width="15%" class="text-center">Start</th>
                                    <th width="15%" class="text-center">End</th>
                                </tr>
                            </thead>
                            <tbody id="mSubjectsList">
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-mouse-pointer"></i><br>
                                        Select a package above to view its details.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            
                <div class="details-actions">
                    <button type="button" class="btn btn-neutral" onclick="closeDetails()">
                        <i class="fas fa-times"></i> Close Details
                    </button>
                </div>
            </div>
        </div>
    </main>

    <div class="floating-bar" id="payBar">
        <div class="bar-info">
            <span class="bar-label" id="payBarLabel">Selection:</span>
            <span class="bar-total" id="selectedTotal"></span>
        </div>
        <button id="payBarBtn" class="btn btn-primary btn-proceed" onclick="showConfirmPopup()">
            Register Now <i class="fas fa-arrow-right"></i>
        </button>
    </div>

    <div id="confirmPopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div id="confirmIcon" class="popup-icon-container">
                <i class="fas"></i>
            </div>
            <h2 id="confirmTitle">Confirm Action</h2>
            <p id="confirmMessage">Are you sure?</p>
            <div class="popup-buttons">
                <button id="btnCancelConfirm" class="btn btn-neutral">Cancel</button>
                <button id="btnYesConfirm" class="btn btn-primary">Yes, Proceed</button>
            </div>
        </div>
    </div>

    <div id="messagePopup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <div id="msgIcon" class="popup-icon-container">
                <i class="fas"></i>
            </div>
            <h2 id="msgTitle">Success</h2>
            <p id="msgBody">Operation completed.</p>
            <div class="popup-buttons">
                <button id="btnOkMessage" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script>
        // --- GLOBAL STATE ---
        let currentTab = 'package';
        let currentPkgId = null; 
        let currentAction = null;
        let currentMsgAction = null;

        // --- STICKY FOOTER LOGIC ---
        function updateFooter() {
            const footer = document.getElementById('payBar');
            const label = document.getElementById('payBarLabel');
            const value = document.getElementById('selectedTotal');
            const btn = document.getElementById('payBarBtn');

            if (currentTab === 'subject') {
                const checkboxes = document.querySelectorAll('.hidden-checkbox:checked');
                let total = 0;
                let count = checkboxes.length;

                checkboxes.forEach(cb => { total += parseFloat(cb.getAttribute('data-price')); });

                if (count > 0) {
                    label.innerText = count + (count === 1 ? " Subject Selected:" : " Subjects Selected:");
                    value.innerText = "RM " + total.toFixed(2);
                    btn.innerHTML = "Register Selected <i class='fas fa-arrow-right'></i>";
                    footer.classList.add('active');
                } else {
                    footer.classList.remove('active');
                }
            }
        }

        // --- TABS LOGIC ---
        const tabs = document.querySelectorAll(".tab-btn");
        const contents = document.querySelectorAll(".tab-content");
        
        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                tabs.forEach(t => t.classList.remove("active"));
                contents.forEach(c => c.classList.remove("active"));
                
                tab.classList.add("active");
                currentTab = tab.dataset.tab;
                document.getElementById(currentTab).classList.add("active");
                document.getElementById('payBar').classList.remove('active');
                closeDetails();
                
                if(currentTab === 'subject') {
                    updateFooter(); 
                } else {
                    document.querySelectorAll("#package tbody tr").forEach(row => row.classList.remove("selected-row"));
                    currentPkgId = null;
                }
            });
        });

        // --- PACKAGE SELECTION ---
        function selectPackageRow(id, code, name, fee) {
            document.querySelectorAll("#package tbody tr").forEach(row => row.classList.remove("selected-row"));
            document.getElementById("pkg-row-" + id).classList.add("selected-row");
            
            viewPackage(id, code, name, fee);
            
            currentPkgId = id;
            const footer = document.getElementById('payBar');
            document.getElementById('payBarLabel').innerText = "Package Selected:";
            document.getElementById('selectedTotal').innerText = name + " (RM " + parseFloat(fee).toFixed(2) + ")";
            document.getElementById('payBarBtn').innerHTML = "Register Package <i class='fas fa-arrow-right'></i>";
            footer.classList.add('active');
        }

        function viewPackage(id, code, name, fee) {
            document.getElementById("mCode").innerText = code;
            document.getElementById("mName").innerText = name;
            document.getElementById("mPrice").innerText = "RM " + parseFloat(fee).toFixed(2);
            
            const tableBody = document.getElementById("mSubjectsList");
            tableBody.innerHTML = "<tr><td colspan='5' class='empty-state'><i class='fas fa-spinner fa-spin'></i><br>Loading details...</td></tr>";
            
            $.get("{{ url('packages') }}/" + encodeURIComponent(id) + "/subjects", function(subjects) {
                let rows = "";
                if (subjects && subjects.length > 0) {
                	subjects.forEach(sub => {
                        rows += "<tr>" +
                                "<td class='text-center'><span class='desc-sub'>" + sub.id + "</span></td>" +
                                "<td class='text-left text-bold'>" + sub.name + "</td>" +
                                "<td class='text-center'><span class='badge badge-pending'>" + sub.day + "</span></td>" +
                                "<td class='text-center'>" + sub.start + "</td>" +
                                "<td class='text-center'>" + sub.end + "</td>" +
                                "</tr>";
                   });
                    tableBody.innerHTML = rows;
                } else {
                    tableBody.innerHTML = "<tr><td colspan='5' class='empty-state'>No subjects found.</td></tr>";
                }
            });

            const wrapper = document.getElementById("details-view-wrapper");
            wrapper.style.display = "block";
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeDetails() {
            document.getElementById("details-view-wrapper").style.display = "none";
            document.getElementById('payBar').classList.remove('active'); 
            currentPkgId = null;
            document.querySelectorAll("#package tbody tr").forEach(row => { row.classList.remove("selected-row"); });
        }

        // --- SUBJECT SELECTION ---
        function toggleSubjectRow(row) {
		    const checkbox = row.querySelector(".hidden-checkbox");
		    if (checkbox) {
		        checkbox.checked = !checkbox.checked;
		        if (checkbox.checked) { row.classList.add("selected-row"); } 
                else { row.classList.remove("selected-row"); }
		        updateFooter();
		    }
		}

        // --- POPUP LOGIC ---
        function showSystemMessage(type, title, message, actionCallback = null) {
            const iconContainer = document.getElementById('msgIcon');
            const icon = iconContainer.querySelector('i');
            
            iconContainer.className = 'popup-icon-container'; // Reset
            icon.className = 'fas';

            if(type === 'success') {
                iconContainer.classList.add('success');
                icon.classList.add('fa-check-circle');
            } else {
                iconContainer.classList.add('error');
                icon.classList.add('fa-times-circle');
            }

            document.getElementById('msgTitle').innerText = title;
            document.getElementById('msgBody').innerText = message;
            currentMsgAction = actionCallback;
            document.getElementById('messagePopup').classList.add('active');
        }

        function triggerConfirm(type, title, message, actionCallback) {
            const iconContainer = document.getElementById('confirmIcon');
            const icon = iconContainer.querySelector('i');
            const yesBtn = document.getElementById('btnYesConfirm');

            iconContainer.className = 'popup-icon-container'; // Reset
            icon.className = 'fas';

            if (type === 'warning') { // Drop
                iconContainer.classList.add('warning');
                icon.classList.add('fa-trash-alt');
                yesBtn.className = 'btn btn-danger';
                yesBtn.innerHTML = "<i class='fas fa-trash-alt'></i> Yes, Drop It";
            } else { // Register
                iconContainer.classList.add('info');
                icon.classList.add('fa-clipboard-user');
                yesBtn.className = 'btn btn-primary';
                yesBtn.innerHTML = "<i class='fas fa-check'></i> Yes, Register";
            }

            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmMessage').innerText = message;
            currentAction = actionCallback;
            document.getElementById('confirmPopup').classList.add('active');
        }

        document.getElementById('btnCancelConfirm').onclick = function() {
            document.getElementById('confirmPopup').classList.remove('active');
            currentAction = null;
        };

        document.getElementById('btnYesConfirm').onclick = function() {
            if(currentAction) currentAction();
            document.getElementById('confirmPopup').classList.remove('active');
        };

        document.getElementById('btnOkMessage').onclick = function() {
            document.getElementById('messagePopup').classList.remove('active');
            if (currentMsgAction) {
                currentMsgAction();
            } else {
                if(window.location.search.includes('status=') || window.location.search.includes('error=')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        };

        // --- ACTIONS ---
        function showConfirmPopup() {
            if (currentTab === 'package') {
                if(!currentPkgId) return;
                const pkgName = document.getElementById('selectedTotal').innerText;
                triggerConfirm('info', "Confirm Package", "Ready to register for " + pkgName + "?", function() {
                    confirmReg('package');
                });
            } else {
                const checkboxes = document.querySelectorAll('.hidden-checkbox:checked');
                if(checkboxes.length === 0) return;
                const totalText = document.getElementById('selectedTotal').innerText;
                triggerConfirm('info', "Confirm Subjects", "Ready to register " + checkboxes.length + " subjects for " + totalText + "?", function() {
                    confirmReg('subject');
                });
            }
        }

        function confirmDropSubject(regId, name) {
            triggerConfirm('warning', "Drop " + name + "?", "This will remove it from your schedule and clear the unpaid bill. This cannot be undone.", function() {
                submitAction("{{ url('student/registrations') }}/" + encodeURIComponent(regId) + "/drop");
            });
        }

        function confirmReg(type) {
            const data = (type === 'package') ? { type: 'package', id: currentPkgId } : $("#subjectForm").serialize() + "&type=subject";
            $.post("{{ route('student.register') }}", data, function(res) {
                let responseText = res.trim();
                if(responseText === "success") {
                    window.location.href = "{{ route('student.subjects.index') }}?status=success&type=" + type;
                } else {
                    showSystemMessage('error', "Registration Failed", responseText);
                }
            }).fail(function() {
                showSystemMessage('error', "Server Error", "Could not connect to server.");
            });
        }

        // --- URL ERROR HANDLER ---
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const error = urlParams.get('error');

        const regType = urlParams.get('type');

        if (status === 'dropped') {
            const droppedWhat = (regType === 'package') ? 'package' : 'subject';
            showSystemMessage('success', 'Dropped Successfully', 'The ' + droppedWhat + ' was removed from your schedule.');
        }
        else if (status === 'success') {
            const successMsg = (regType === 'package')
                ? 'Your package has been registered. Proceeding to payment...'
                : 'Your subjects have been registered. Proceeding to payment...';
            showSystemMessage('success', 'Registration Successful!', successMsg, function() {
                window.location.href = "{{ route('student.payments.index') }}";
            });
        }
        else if (error === 'subscriptionActive') { showSystemMessage('error', 'Action Denied', 'This subject is already paid. Only unpaid subjects can be dropped here - please contact admin to drop a paid subject.'); }
        else if (error === 'pendingVerify') { showSystemMessage('error', 'Verification in Progress', 'You cannot drop this subject while the payment is pending verification.'); } 
        else if (error === 'dropfailed') { showSystemMessage('error', 'Error', 'Could not drop subject. Please try again later.'); } 
        else if (error) { showSystemMessage('error', 'Error', 'An unexpected error occurred: ' + error); }

        // Opened from "View" on the dashboard (?package=ID): show that package's details straight away.
        const wantedPackage = urlParams.get('package');
        if (wantedPackage) {
            const packageRow = document.getElementById('pkg-row-' + wantedPackage);
            if (packageRow) packageRow.click();
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>