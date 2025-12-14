<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
$func = new Functions();
$message = $error = "";

// --- AJAX Endpoint to fetch booked slots ---
if (isset($_GET['booked_times']) && isset($_GET['facility_id']) && isset($_GET['date'])) {
    $facilityId = intval($_GET['facility_id']);
    $resDate = $_GET['date'];

    $booked_slots = $func->fetchAll(
        "SELECT start_time, end_time FROM reservation 
         WHERE facility_id=? AND date=? AND status IN ('pending','approved')
         ORDER BY start_time",
        [$facilityId, $resDate],
        "is"
    );

    header('Content-Type: application/json');
    echo json_encode($booked_slots);
    exit;
}
// ------------------------------------------

// Preserve previous values
$prev = [
    'facility_id' => '',
    'date' => '',
    'start_time' => '',
    'end_time' => '',
    'purpose' => '',
    'duration' => 1
];

// Fetch all facilities
$facilities = $func->getFacilities();

// --- TIME SLOT GENERATION (06:00 to 20:00 max) ---
$time_slots = [];
for ($h = 6; $h <= 20; $h++) {
    foreach ([0, 30] as $m) {
        $time = sprintf("%02d:%02d", $h, $m);
        if ($time <= '20:00') {
            $time_slots[] = $time;
        }
    }
}

// --- START TIME CONSTRAINT: 06:00 to 16:00 ---
$start_time_slots = array_filter($time_slots, function ($time) {
    return $time >= '06:00' && $time <= '16:00';
});
// ------------------------------------------

// Handle reservation submission (Server-side validation remains the ultimate safeguard)
if (isset($_POST['reserve'])) {
    $prev['facility_id'] = intval($_POST['facility_id']);
    $prev['date'] = $_POST['date'];
    $prev['start_time'] = $_POST['start_time'];
    $prev['end_time'] = $_POST['end_time'];
    $prev['purpose'] = trim($_POST['purpose']);
    $prev['duration'] = intval($_POST['duration']);

    if ($prev['facility_id'] && $prev['date'] && $prev['start_time'] && $prev['end_time'] && $prev['purpose'] && $prev['duration'] > 0) {
        $conflict_found = false;
        for ($i = 0; $i < $prev['duration']; $i++) {
            $res_date = date('Y-m-d', strtotime($prev['date'] . " +$i days"));
            $conflict = $func->fetchOne(
                "SELECT * FROM reservation 
                 WHERE facility_id=? AND date=? AND status IN ('pending','approved')
                 AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))",
                [$prev['facility_id'], $res_date, $prev['end_time'], $prev['start_time'], $prev['end_time'], $prev['start_time']],
                "isssss"
            );

            if ($conflict) {
                $conflict_found = true;
                $error = "Conflict: Facility already booked on <strong>$res_date</strong> for selected time.";
                break;
            }
        }

        if (!$conflict_found) {
            for ($i = 0; $i < $prev['duration']; $i++) {
                $res_date = date('Y-m-d', strtotime($prev['date'] . " +$i days"));
                $func->execute(
                    "INSERT INTO reservation (user_id, facility_id, date, start_time, end_time, purpose) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$user['user_id'], $prev['facility_id'], $res_date, $prev['start_time'], $prev['end_time'], $prev['purpose']],
                    "iissss"
                );
            }
            $message = "Reservation submitted successfully for {$prev['duration']} day(s)!";
            $prev = ['facility_id' => '', 'date' => '', 'start_time' => '', 'end_time' => '', 'purpose' => '', 'duration' => 1];
        }
    } else {
        $error = "Please fill in all required fields and ensure duration is at least 1 day.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Facility | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/user.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        html,
        body {
            overflow-y: hidden;
        }

        .user-content {
            overflow-y: auto;
            max-height: calc(100vh - 40px);
            /* Adjusting height for better scrolling within the frame */
            padding-bottom: 2rem !important;
        }
    </style>
</head>

<body>
    <div class="user-content container-fluid px-4 py-4">
        <div class="content-header mb-4">
            <h3 class="fw-semibold mb-0">Reserve a Facility</h3>
            <p class="text-muted mb-0 mt-1">Select a facility, date, time, and duration for your reservation.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-darkred text-white fw-semibold">
                <i class="bi bi-calendar-plus"></i> Reservation Form
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="facility_id" class="form-label">Facility</label>
                        <select name="facility_id" id="facility_id" class="form-select" required>
                            <option value="">-- Choose Facility --</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['facility_id'] ?>"
                                    <?= $facility['facility_id'] == $prev['facility_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['name']) ?> (Cap: <?= $facility['capacity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" min="<?= date('Y-m-d') ?>"
                            value="<?= htmlspecialchars($prev['date']) ?>" required <?= $prev['facility_id'] ? '' : 'disabled' ?>>
                    </div>

                    <div class="col-md-3">
                        <label for="duration" class="form-label">Duration (days)</label>
                        <input type="number" name="duration" id="duration" class="form-control" min="1"
                            value="<?= htmlspecialchars($prev['duration']) ?>" required <?= $prev['facility_id'] ? '' : 'disabled' ?>>
                    </div>

                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Start Time (06:00 - 16:00)</label>
                        <select name="start_time" id="start_time" class="form-select" required <?= ($prev['facility_id'] && $prev['date']) ? '' : 'disabled' ?>>
                            <option value="">-- Select Start Time --</option>
                            <?php foreach ($start_time_slots as $time): ?>
                                <option value="<?= $time ?>" <?= $prev['start_time'] == $time ? 'selected' : '' ?>><?= $time ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="end_time" class="form-label">End Time (Max 20:00)</label>
                        <select name="end_time" id="end_time" class="form-select" <?= $prev['start_time'] ? '' : 'disabled' ?> required>
                            <option value="">-- Select End Time --</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="purpose" class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" placeholder="E.g., Pickle Ball Game"
                            value="<?= htmlspecialchars($prev['purpose']) ?>" required>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" name="reserve" class="btn btn-submit">Submit Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const facilitySelect = document.getElementById('facility_id');
        const dateInput = document.getElementById('date');
        const durationInput = document.getElementById('duration');
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');

        // Retrieve the full list of allowed start times from the populated dropdown options
        const allStartTimes = Array.from(startTimeSelect.options).map(o => o.value).filter(v => v);
        const prevStartTime = '<?= htmlspecialchars($prev['start_time']) ?>';
        const prevEndTime = '<?= htmlspecialchars($prev['end_time']) ?>';

        // --- Function to build the FULL time slots array (06:00 to 20:00) in JS for End Time population ---
        function getFullTimeSlots() {
            let slots = [];
            for (let h = 6; h <= 20; h++) {
                for (let m of [0, 30]) {
                    const time = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    if (time <= '20:00') {
                        slots.push(time);
                    }
                }
            }
            return slots;
        }
        const fullTimeSlots = getFullTimeSlots();


        // --- 1. Filter and Populate Start Times (Checks conflicts against existing bookings) ---
        function updateStartTimes() {
            const facilityId = facilitySelect.value;
            const date = dateInput.value;

            // Reset and Populate Start Times to default (06:00-16:00)
            startTimeSelect.innerHTML = '<option value="">-- Select Start Time --</option>';
            allStartTimes.forEach(time => {
                const opt = new Option(time, time);
                startTimeSelect.appendChild(opt);
            });

            // Disable End Time selection
            endTimeSelect.innerHTML = '<option value="">-- Select End Time --</option>';
            endTimeSelect.disabled = true;

            // Conditional Enabling/Disabling based on selection
            const isReady = facilityId && date;

            if (!isReady) {
                startTimeSelect.disabled = true;
                return;
            }

            startTimeSelect.disabled = false;

            // Fetch Booked Slots to determine available START times
            // FIX: Changed fetch URL from reserve_facility.php to admin_reserve.php
            fetch(`admin_reserve.php?booked_times=1&facility_id=${facilityId}&date=${date}`)
                .then(res => res.json())
                .then(bookedSlots => {
                    // Loop through existing bookings
                    bookedSlots.forEach(slot => {
                        const bookingStart = slot.start_time;
                        const bookingEnd = slot.end_time;

                        // Check every possible START time option
                        Array.from(startTimeSelect.options).forEach(opt => {
                            const checkTime = opt.value;
                            if (!checkTime) return;

                            // If checkTime falls inside an existing booking range (start inclusive, end exclusive)
                            if (checkTime >= bookingStart && checkTime < bookingEnd) {
                                opt.disabled = true;
                                opt.textContent;
                            }
                        });
                    });

                    // Re-select previous start time if it's still available
                    if (prevStartTime) {
                        const opt = startTimeSelect.querySelector(`option[value="${prevStartTime}"]`);
                        if (opt && !opt.disabled) {
                            opt.selected = true;
                            // If the form previously failed validation, re-run end time update
                            if (prevEndTime) updateEndTimes();
                        } else if (opt) {
                            // If previously selected time is now disabled, unselect it
                            startTimeSelect.value = '';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching booked times for Start Time filtering:', error);
                });
        }


        // --- 2. Filter and Populate End Times (Only shows available times) ---
        function updateEndTimes() {
            const facilityId = facilitySelect.value;
            const date = dateInput.value;
            const startTime = startTimeSelect.value;

            // Reset End Time
            endTimeSelect.innerHTML = '<option value="">-- Select End Time --</option>';

            if (!startTime) {
                endTimeSelect.disabled = true;
                return;
            }

            endTimeSelect.disabled = false;

            if (!facilityId || !date) {
                // Populate with all possible times > startTime, if AJAX can't run
                const fallbackTimes = fullTimeSlots.filter(t => t > startTime);
                fallbackTimes.forEach(t => {
                    const opt = new Option(t, t);
                    if (t === prevEndTime) opt.selected = true;
                    endTimeSelect.appendChild(opt);
                });
                return;
            }

            // Fetch Booked Slots to determine available END times
            // FIX: Changed fetch URL from reserve_facility.php to admin_reserve.php
            fetch(`admin_reserve.php?booked_times=1&facility_id=${facilityId}&date=${date}`)
                .then(res => res.json())
                .then(bookedSlots => {
                    let earliestConflictEnd = null;

                    // Find the earliest existing booking that starts AFTER or AT the user's selected start time.
                    bookedSlots.forEach(slot => {
                        const bookingStart = slot.start_time;

                        // We are only interested in a conflict *after* the user's start time
                        if (bookingStart > startTime) {
                            // Update earliestConflictEnd to the earliest start time we find
                            if (!earliestConflictEnd || bookingStart < earliestConflictEnd) {
                                earliestConflictEnd = bookingStart;
                            }
                        }
                    });

                    // Filter the full time slots array for available times
                    const availableEndTimes = fullTimeSlots
                        .filter(t => t > startTime) // 1. Must be after the selected start time
                        .filter(t => {
                            // 2. Must be BEFORE the earliest conflicting booking's start time (exclusive)
                            if (earliestConflictEnd) {
                                return t < earliestConflictEnd;
                            }
                            return true;
                        });

                    // Populate the dropdown with ONLY the available times
                    availableEndTimes.forEach(t => {
                        const opt = new Option(t, t);
                        if (t === prevEndTime) opt.selected = true;
                        endTimeSelect.appendChild(opt);
                    });
                })
                .catch(error => {
                    console.error('Error fetching booked times for End Time filtering:', error);
                    // Fallback: If fetch fails, populate with all times > startTime
                    const fallbackTimes = fullTimeSlots.filter(t => t > startTime);
                    fallbackTimes.forEach(t => {
                        const opt = new Option(t, t);
                        if (t === prevEndTime) opt.selected = true;
                        endTimeSelect.appendChild(opt);
                    });
                });
        }

        // --- 3. Cascading Form Logic and Event Listeners ---

        function updateFormDependencies() {
            const facilityId = facilitySelect.value;
            const date = dateInput.value;

            // A. Facility -> Date/Duration
            if (facilityId) {
                dateInput.disabled = false;
                durationInput.disabled = false;
            } else {
                dateInput.disabled = true;
                durationInput.disabled = true;
                // Reset and disable subsequent fields
                dateInput.value = '';
                startTimeSelect.disabled = true;
                endTimeSelect.disabled = true;
                startTimeSelect.selectedIndex = 0;
                endTimeSelect.innerHTML = '<option value="">-- Select End Time --</option>';
                return;
            }

            // B. Date -> Start Time (and trigger start time filtering)
            if (date) {
                updateStartTimes();
            } else {
                startTimeSelect.disabled = true;
                endTimeSelect.disabled = true;
                startTimeSelect.selectedIndex = 0;
                endTimeSelect.innerHTML = '<option value="">-- Select End Time --</option>';
            }
        }

        // Event listeners
        facilitySelect.addEventListener('change', () => {
            // Clear selections that depend on facility when facility changes
            dateInput.value = '';
            startTimeSelect.value = '';
            endTimeSelect.value = '';
            updateFormDependencies();
        });
        dateInput.addEventListener('change', () => {
            // Clear selections that depend on date when date changes
            startTimeSelect.value = '';
            endTimeSelect.value = '';
            updateFormDependencies();
        });
        startTimeSelect.addEventListener('change', updateEndTimes);

        // Initial call to set up the form state based on previous values (if any)
        updateFormDependencies();
        const prevStartOpt = startTimeSelect.querySelector(`option[value="${prevStartTime}"]`);
        if (prevStartOpt && prevStartOpt.selected && prevEndTime) {
            // Only run endTimes update if the previously selected start time is still active
            updateEndTimes();
        }
    </script>
    <script>
        // Notify parent frame about current page
        (function () {
            const currentPage = window.location.pathname.split('/').pop();

            // Announce page on load
            function announcePage() {
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'pageChanged',
                        page: currentPage
                    }, '*');
                }
            }

            // Announce immediately
            announcePage();

            // Listen for parent's request
            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });

            // Intercept navigation links (for "View details" etc.)
            document.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link) return;

                const href = link.getAttribute('href');

                // Check if it's an internal page navigation
                if (href && !href.startsWith('http') && !href.startsWith('#') &&
                    !href.includes('?action=') && href.endsWith('.php')) {

                    // Announce the target page
                    const targetPage = href.split('/').pop();
                    if (window.parent !== window) {
                        window.parent.postMessage({
                            type: 'pageChanged',
                            page: targetPage
                        }, '*');
                    }
                }
            });
        })();
    </script>
</body>

</html>