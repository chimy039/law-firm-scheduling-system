<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/config/send_email.php";
session_start();

if (empty($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) exit("Invalid ID");

$stmt = $conn->prepare("
  SELECT a.appointment_id, a.attorney_name, a.title, a.status,
         a.client_name, a.client_email,
         t.start_time, t.end_time, t.timezone
  FROM appointments a
  JOIN appointment_times t ON t.appointment_id = a.appointment_id
  WHERE a.appointment_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meeting) exit("Meeting not found.");

$conflictStmt = $conn->prepare("
  SELECT a.appointment_id, a.title, t.start_time, t.end_time
  FROM appointments a
  JOIN appointment_times t ON t.appointment_id = a.appointment_id
  WHERE a.attorney_name = ?
    AND a.status IN ('scheduled','confirmed')
    AND a.appointment_id <> ?
    AND NOT (t.end_time <= ? OR t.start_time >= ?)
  ORDER BY t.start_time ASC
  LIMIT 5
");
$conflictStmt->bind_param(
  "siss",
  $meeting["attorney_name"],
  $id,
  $meeting["start_time"],
  $meeting["end_time"]
);
$conflictStmt->execute();
$conflicts = $conflictStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conflictStmt->close();

$email_status = "";

/* Confirm + email on POST */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $upd = $conn->prepare("UPDATE appointments SET status='confirmed' WHERE appointment_id=?");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();

    // Send email if client_email exists
    if (!empty($meeting["client_email"])) {
        $to = $meeting["client_email"];
        $name = $meeting["client_name"] ?: "Client";

        $subject = "Meeting Confirmed: " . $meeting["title"];

        $body =
            "Hello $name,\n\n" .
            "Your meeting has been confirmed.\n\n" .
            "Title: " . $meeting["title"] . "\n" .
            "Start: " . $meeting["start_time"] . "\n" .
            "End: " . $meeting["end_time"] . "\n" .
            "Timezone: " . $meeting["timezone"] . "\n\n" .
            "If you need to reschedule, reply to this email.\n\n" .
            "Thank you.";

        $ok = send_client_email($to, $name, $subject, $body);

       
    }

    header("Location: list_meetings.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Confirm Meeting</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

  <div class="nav">
    <div>
      <strong>Confirm Meeting</strong>
      <div class="small">Review conflicts before confirming</div>
    </div>
    <div class="actions">
      <a class="btn btn-light" href="list_meetings.php">Back</a>
      <a class="btn btn-red" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="meeting-card">
    <div class="meeting-title"><?php echo h($meeting["title"]); ?></div>

    <div class="meeting-meta">
      <strong>Client:</strong> <?php echo h($meeting["client_name"]); ?>
      <?php if (!empty($meeting["client_email"])): ?>
        <span class="small">(<?php echo h($meeting["client_email"]); ?>)</span>
      <?php else: ?>
        <span class="small">(no email on file)</span>
      <?php endif; ?>
    </div>

    <div class="meeting-meta">
      <strong>Attorney:</strong> <?php echo h($meeting["attorney_name"]); ?>
    </div>

    <div class="meeting-meta">
      <strong>When:</strong>
      <?php $start = date("M j, Y g:i A", strtotime($meeting["start_time"]));
$end   = date("g:i A", strtotime($meeting["end_time"]));
echo $start . " → " . $end;?>
    </div>

    <?php if (!empty($conflicts)): ?>
      <div class="msg" style="background:#ffe9e9;">
        <strong>⚠ Time conflict detected:</strong>
        <ul>
          <?php foreach ($conflicts as $c): ?>
            <li>
              #<?php echo (int)$c["appointment_id"]; ?> —
              <?php echo h($c["title"]); ?> —
              <?php echo h($c["start_time"]); ?> → <?php echo h($c["end_time"]); ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="small">You can still confirm, but it may double-book.</div>
      </div>
    <?php else: ?>
      <div class="msg" style="background:#e9ffe9;">✅ No conflicts found.</div>
    <?php endif; ?>

    <form method="post" class="actions" style="justify-content:flex-end;">
      <button class="btn btn-dark" type="submit">Confirm & Email Client</button>
      <a class="btn btn-light" href="edit_meeting.php?id=<?php echo (int)$meeting["appointment_id"]; ?>">Edit Time</a>
      <a class="btn btn-light" href="list_meetings.php">Cancel</a>
    </form>
  </div>

</div>
</body>
</html>