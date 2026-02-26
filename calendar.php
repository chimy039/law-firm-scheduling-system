<?php
require __DIR__ . "/config/auth.php";
require_login();
$u = current_user();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Calendar</title>
  <link rel="stylesheet" href="assets/style.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body>
  <div class="nav">
    <div>
      <strong>Calendar</strong>
      <div class="small">Logged in as <?= h($u["full_name"]) ?></div>
    </div>
    <div class="actions">
      <a class="btn btn-light" href="dashboard.php">Dashboard</a>
      <a class="btn btn-light" href="list_meetings.php">Meetings</a>
      <a class="btn btn-dark" href="create_meeting.php">+ New</a>
      <a class="btn btn-red" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="card">
    <div id="cal"></div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const calEl = document.getElementById('cal');
      const cal = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        height: 720,
        events: 'events.php',
        eventClick: function(info) {
          const id = info.event.extendedProps.appointment_id;
          if (id) window.location = 'edit_meeting.php?id=' + id;
        }
      });
      cal.render();
    });
  </script>
</body>
</html>