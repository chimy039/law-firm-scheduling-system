<?php
require __DIR__ . "/config/db.php";
session_start();

// OPTIONAL: if you're doing auth redirects, keep this
if (empty($_SESSION["user"])) {
  header("Location: login.php");
  exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  exit("Missing or invalid meeting id.");
}

$msg = "";

/* Load existing meeting */
$stmt = $conn->prepare("
  SELECT a.appointment_id, a.client_name, a.attorney_name, a.title,
         a.description, a.location, a.status,
         t.start_time, t.end_time, t.timezone, t.type
  FROM appointments a
  JOIN appointment_times t ON t.appointment_id = a.appointment_id
  WHERE a.appointment_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meeting) {
  exit("Meeting not found (id=$id).");
}

/* Helper: MySQL DATETIME -> datetime-local value */
function dt_to_local($dt) {
  // "2026-02-26 13:30:00" -> "2026-02-26T13:30"
  return str_replace(" ", "T", substr($dt, 0, 16));
}

/* Save updates */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $client_name   = trim($_POST["client_name"] ?? "");
  $attorney_name = trim($_POST["attorney_name"] ?? "");
  $title         = trim($_POST["title"] ?? "");
  $description   = trim($_POST["description"] ?? "");
  $location      = trim($_POST["location"] ?? "");
  $status        = $_POST["status"] ?? "scheduled";

  $start_time = str_replace("T", " ", $_POST["start_time"] ?? "");
  $end_time   = str_replace("T", " ", $_POST["end_time"] ?? "");

  $timezone = trim($_POST["timezone"] ?? "America/New_York");
  $type     = $_POST["type"] ?? "meeting";

  if ($client_name === "" || $attorney_name === "" || $title === "" || $start_time === "" || $end_time === "") {
    $msg = "Fill in all required fields.";
  } elseif (strtotime($end_time) <= strtotime($start_time)) {
    $msg = "End time must be after start time.";
  } else {

    // Update appointments table
    $s1 = $conn->prepare("
      UPDATE appointments
      SET client_name=?, attorney_name=?, title=?, description=?, location=?, status=?
      WHERE appointment_id=?
    ");
    $s1->bind_param("ssssssi", $client_name, $attorney_name, $title, $description, $location, $status, $id);

    // Update appointment_times table (for that appointment_id)
    $s2 = $conn->prepare("
      UPDATE appointment_times
      SET start_time=?, end_time=?, timezone=?, type=?
      WHERE appointment_id=?
    ");
    $s2->bind_param("ssssi", $start_time, $end_time, $timezone, $type, $id);

    if ($s1->execute() && $s2->execute()) {
      // Go back to list after saving
      header("Location: list_meetings.php");
      exit;
    } else {
      $msg = "Update failed: " . $conn->error;
    }

    $s1->close();
    $s2->close();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Meeting</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="nav">
  <div>
    <strong>Edit Meeting #<?= (int)$meeting["appointment_id"] ?></strong>
    <div class="small">Update details and time</div>
  </div>
  <div class="actions">
    <a class="btn btn-light" href="list_meetings.php">Back</a>
    <a class="btn btn-light" href="create_meeting.php">New Meeting</a>
  </div>
</div>

<div class="card">
  <?php if ($msg): ?>
    <div class="msg"><?= h($msg) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="grid">
      <div>
        <label>Client Name *</label>
        <input name="client_name" value="<?= h($meeting["client_name"]) ?>" required>
      </div>
      <div>
        <label>Attorney Name *</label>
        <input name="attorney_name" value="<?= h($meeting["attorney_name"]) ?>" required>
      </div>
    </div>

    <label>Title *</label>
    <input name="title" value="<?= h($meeting["title"]) ?>" required>

    <label>Description</label>
    <textarea name="description"><?= h($meeting["description"] ?? "") ?></textarea>

    <label>Location</label>
    <input name="location" value="<?= h($meeting["location"] ?? "") ?>">

    <div class="grid">
      <div>
        <label>Start Time *</label>
        <input type="datetime-local" name="start_time" value="<?= h(dt_to_local($meeting["start_time"])) ?>" required>
      </div>
      <div>
        <label>End Time *</label>
        <input type="datetime-local" name="end_time" value="<?= h(dt_to_local($meeting["end_time"])) ?>" required>
      </div>
    </div>

    <div class="grid">
      <div>
        <label>Status</label>
        <select name="status">
          <?php foreach (["scheduled","confirmed","completed","cancelled","no_show"] as $s): ?>
            <option value="<?= h($s) ?>" <?= ($meeting["status"]===$s)?"selected":"" ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Type</label>
        <select name="type">
          <?php foreach (["meeting","call","court","deadline","other"] as $t): ?>
            <option value="<?= h($t) ?>" <?= ($meeting["type"]===$t)?"selected":"" ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <label>Timezone</label>
    <input name="timezone" value="<?= h($meeting["timezone"]) ?>">

    <button class="btn btn-dark" type="submit" style="margin-top:14px;">Save Changes</button>
  </form>
</div>

</body>
</html>