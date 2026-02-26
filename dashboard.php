<?php
require __DIR__ . "/config/db.php";
session_start();

if (empty($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pendingCount = 0;
$upcomingCount = 0;

$r1 = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='pending'");
if ($r1) { $pendingCount = (int)$r1->fetch_assoc()["c"]; }

$r2 = $conn->query("
  SELECT COUNT(*) AS c
  FROM appointments a
  JOIN appointment_times t ON t.appointment_id = a.appointment_id
  WHERE a.status IN ('scheduled','confirmed')
    AND t.start_time >= NOW()
");
if ($r2) { $upcomingCount = (int)$r2->fetch_assoc()["c"]; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .stats{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .stat{background:#fff;border:1px solid #ddd;border-radius:14px;padding:16px;}
    .num{font-size:28px;font-weight:700;margin-top:6px;}
    @media(max-width:800px){.stats{grid-template-columns:1fr;}}
  </style>
</head>
<body>

<div class="container">

  <div class="nav">
    <div>
      <strong>Dashboard</strong>
      <div class="small">Welcome back, <?php echo h($_SESSION["user"]["full_name"]); ?></div>
    </div>
    <div class="actions">
      <a class="btn btn-dark" href="list_meetings.php">Open Meetings</a>
      <a class="btn btn-light" href="create_meeting.php">New Meeting</a>
      <a class="btn btn-red" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="stats">

    <div class="stat">
      <div class="small">Pending Requests</div>
      <div class="num"><?php echo $pendingCount; ?></div>
      <div class="small">Awaiting review</div>
    </div>

    <div class="stat">
      <div class="small">Upcoming Meetings</div>
      <div class="num"><?php echo $upcomingCount; ?></div>
      <div class="small">Scheduled or confirmed</div>
    </div>

  </div>

</div>
</body>
</html>