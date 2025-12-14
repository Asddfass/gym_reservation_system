<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../css/admin.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        .fc {
            font-family: 'Inter', 'Poppins', sans-serif;
        }

        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 600 !important;
        }

        .fc-button-primary {
            background: linear-gradient(135deg, #a4161a 0%, #dc2f02 100%) !important;
            border: none !important;
        }

        .fc-button-primary:hover {
            background: linear-gradient(135deg, #660708 0%, #a4161a 100%) !important;
        }

        .fc-button-active {
            background: linear-gradient(135deg, #660708 0%, #a4161a 100%) !important;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 6px !important;
            padding: 3px 8px !important;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 2px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .fc-daygrid-event {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fc-daygrid-dot-event {
            padding: 2px 4px !important;
        }

        .fc-daygrid-dot-event .fc-event-title {
            font-weight: 600;
        }

        .fc-daygrid-day:hover {
            background: rgba(164, 22, 26, 0.05);
        }

        .fc-day-today {
            background: rgba(164, 22, 26, 0.08) !important;
        }

        /* Override today highlight in timeGrid views to be more subtle */
        .fc-timegrid-col.fc-day-today {
            background: rgba(164, 22, 26, 0.05) !important;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
            font-size: 0.875rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            margin-right: 6px;
        }

        #eventModal .modal-header {
            background: linear-gradient(135deg, #a4161a 0%, #dc2f02 100%);
            color: white;
        }
    </style>
</head>

<body>

    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-semibold text-dark mb-0">Calendar View</h2>
                <p class="text-muted mb-0 mt-1">Visual overview of all facility reservations</p>
            </div>
            <div class="legend d-flex flex-wrap gap-2">
                <span class="legend-item"><span class="legend-color" style="background:#ffc107"></span>Pending</span>
                <span class="legend-item"><span class="legend-color" style="background:#28a745"></span>Approved</span>
                <span class="legend-item"><span class="legend-color" style="background:#dc3545"></span>Denied</span>
                <span class="legend-item"><span class="legend-color" style="background:#6c757d"></span>Cancelled</span>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>Reservation Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Facility</label>
                        <p class="fw-semibold mb-0" id="modalFacility"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Reserved By</label>
                        <p class="fw-semibold mb-0" id="modalUser"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Date & Time</label>
                        <p class="fw-semibold mb-0" id="modalTime"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Purpose</label>
                        <p class="fw-semibold mb-0" id="modalPurpose"></p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small">Status</label>
                        <p class="mb-0"><span class="badge" id="modalStatus"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage_reservations.php" id="manageReservationsLink" class="btn btn-primary">
                        <i class="bi bi-gear me-1"></i>Manage This Reservation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                // Time range settings for week/day views (matches reservation hours)
                slotMinTime: '06:00:00',
                slotMaxTime: '21:00:00',
                // Show end time in events for timeGrid views
                displayEventEnd: true,
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                // Current time indicator
                nowIndicator: true,
                events: '../api/calendar_api.php',
                eventClick: function (info) {
                    const props = info.event.extendedProps;
                    const startDate = info.event.start;

                    document.getElementById('modalFacility').textContent = props.facility;
                    document.getElementById('modalUser').textContent = props.user;
                    document.getElementById('modalTime').textContent =
                        startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) +
                        ' • ' + props.time;
                    document.getElementById('modalPurpose').textContent = props.purpose;

                    const statusBadge = document.getElementById('modalStatus');
                    statusBadge.textContent = props.status.charAt(0).toUpperCase() + props.status.slice(1);
                    statusBadge.className = 'badge bg-' + {
                        'approved': 'success',
                        'denied': 'danger',
                        'cancelled': 'secondary',
                        'pending': 'warning text-dark'
                    }[props.status];

                    // Build the manage reservations link with filter parameters
                    const filterParams = new URLSearchParams({
                        facility_id: props.facility_id,
                        date_from: props.date,
                        date_to: props.date,
                        status: props.status
                    });
                    document.getElementById('manageReservationsLink').href =
                        'manage_reservations.php?' + filterParams.toString();

                    modal.show();
                },
                eventDidMount: function (info) {
                    const props = info.event.extendedProps;
                    const statusLabel = props.status.charAt(0).toUpperCase() + props.status.slice(1);
                    info.el.title = '📍 ' + props.facility + '\n' +
                        '🕐 ' + props.time + '\n' +
                        '👤 Reserved by: ' + props.user + '\n' +
                        '📝 Purpose: ' + props.purpose + '\n' +
                        '📊 Status: ' + statusLabel;
                },
                height: 'auto',
                dayMaxEvents: 3,
                moreLinkClick: 'popover'
            });

            calendar.render();
        });

        // Notify parent frame about current page
        (function () {
            const currentPage = window.location.pathname.split('/').pop();
            function announcePage() {
                if (window.parent !== window) {
                    window.parent.postMessage({ type: 'pageChanged', page: currentPage }, '*');
                }
            }
            announcePage();
            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });
        })();
    </script>
</body>

</html>