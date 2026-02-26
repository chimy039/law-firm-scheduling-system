<?php
require __DIR__ . "/config/db.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $client_name  = trim($_POST["client_name"] ?? "");
  $client_email = trim($_POST["client_email"] ?? "");
  $title        = trim($_POST["title"] ?? "");
  $description  = trim($_POST["description"] ?? "");

  // For now, client just requests. We store as "scheduled" and attorney can confirm later.
  // Also hardcode attorney name since he works alone.
  $attorney_name = "Attorney";
  $location = "";
  $status = "pending";

  // Optional: let client pick preferred time
  $start_time = str_replace("T", " ", $_POST["start_time"] ?? "");
  $end_time   = str_replace("T", " ", $_POST["end_time"] ?? "");
  $timezone   = "America/New_York";
  $type       = "meeting";

  if ($client_name === "" || $client_email === "" || $title === "" || $start_time === "" || $end_time === "") {
    $msg = "Please fill in all required fields.";
  } elseif (strtotime($end_time) <= strtotime($start_time)) {
    $msg = "End time must be after start time.";
  } else {

    $stmt1 = $conn->prepare("
      INSERT INTO appointments (client_name, client_email, attorney_name, title, description, location, status)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt1->bind_param("sssssss", $client_name, $client_email, $attorney_name, $title, $description, $location, $status);

    if ($stmt1->execute()) {
      $appointment_id = $conn->insert_id;

      $stmt2 = $conn->prepare("
        INSERT INTO appointment_times (appointment_id, start_time, end_time, timezone, type)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt2->bind_param("issss", $appointment_id, $start_time, $end_time, $timezone, $type);

      if ($stmt2->execute()) {
        $msg = "✅ Request submitted! The office will contact you to confirm.";
      } else {
        $msg = "❌ Could not save the requested time.";
      }
      $stmt2->close();
    } else {
      $msg = "❌ Could not submit request.";
    }

    $stmt1->close();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Request a Consultation</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

  <div class="nav">
    <div>
      <strong>Request a Consultation</strong>
      <div class="small">Public client form (no login)</div>
    </div>
    <div class="actions">
      <a class="btn btn-light" href="index.php">Home</a>
      <a class="btn btn-light" href="login.php">Lawyer Login</a>
    </div>
  </div>

  <div class="card">
    <?php if ($msg): ?>
      <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Your Name *</label>
      <input name="client_name" required>

      <label>Your Email *</label>
      <input type="email" name="client_email" required>

      <label>Reason for Consultation *</label>
      <input name="title" required>

      <label>Details</label>
      <textarea name="description"></textarea>

      <label>Preferred Start Time *</label>
      <input type="datetime-local" name="start_time" required>

      <label>Preferred End Time *</label>
      <input type="datetime-local" name="end_time" required>

      <button class="btn btn-dark" style="margin-top:14px;">Submit Request</button>
    </form>
  </div>

</div>
</body>
</html>