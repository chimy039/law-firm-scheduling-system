<?php
require __DIR__ . "/config/db.php";
session_start();

if (empty($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }


$sql = "
  SELECT a.appointment_id, a.client_name, a.client_email, a.attorney_name,
         a.title, a.status, a.location,
         t.start_time, t.end_time, t.timezone, t.type
  FROM appointments a
  LEFT JOIN appointment_times t ON t.appointment_id = a.appointment_id
  ORDER BY (t.start_time IS NULL) DESC, t.start_time ASC, a.appointment_id DESC
";
$result = $conn->query($sql);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Meetings</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
  <div class="nav">
    <div>
      <strong>Meetings</strong>
      <div class="small">Logged in as <?php echo h($_SESSION["user"]["full_name"]); ?></div>
    </div>
    <div class="actions">
      <a class="btn btn-light" href="dashboard.php">Dashboard</a>
      <a class="btn btn-dark" href="create_meeting.php">+ New Meeting</a>
      <a class="btn btn-red" href="logout.php">Logout</a>
    </div>
  </div>

  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="meeting-card">

        <div class="meeting-header">
          <div class="meeting-title"><?php echo h($row["title"]); ?></div>
          <div>
            <span class="pill <?php echo h($row["status"]); ?>">
              <?php echo h($row["status"]); ?>
            </span>
          </div>
        </div>

        <div class="meeting-meta">
          <strong>Client:</strong> <?php echo h($row["client_name"]); ?>
          <?php if (!empty($row["client_email"])): ?>
            <span class="small">(<?php echo h($row["client_email"]); ?>)</span>
          <?php endif; ?>
          | <strong>Attorney:</strong> <?php echo h($row["attorney_name"]); ?>
        </div>

        <div class="meeting-meta">
          <strong>When:</strong>
          <?php
            if (!empty($row["start_time"]) && !empty($row["end_time"])) {
              $start = date("M j, Y g:i A", strtotime($row["start_time"]));
              $end   = date("g:i A", strtotime($row["end_time"]));
              $tz    = $row["timezone"] ?: "America/New_York";
              echo h($start . " → " . $end . " (" . $tz . ")");
            } else {
              echo "<span class='small'>No time submitted</span>";
            }
          ?>
        </div>

        <div class="meeting-meta">
          <strong>Type:</strong> <?php echo h($row["type"] ?? "meeting"); ?> |
          <strong>Location:</strong> <?php echo h($row["location"] ?? ""); ?>
        </div>

        <div class="actions">
          <a class="btn btn-dark" href="confirm_meeting.php?id=<?php echo (int)$row["appointment_id"]; ?>">Confirm</a>
          <a class="btn btn-light" href="edit_meeting.php?id=<?php echo (int)$row["appointment_id"]; ?>">Edit</a>
          <a class="btn btn-light" href="cancel_meeting.php?id=<?php echo (int)$row["appointment_id"]; ?>">Cancel</a>
          <a class="btn btn-red" href="delete_meeting.php?id=<?php echo (int)$row["appointment_id"]; ?>" onclick="return confirm('Delete meeting?')">Delete</a>
        </div>

      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No meetings found.</p>
  <?php endif; ?>

</div>
</body>
</html>