<?php
require __DIR__ . "/config/db.php";
session_start();

if (empty($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_name   = trim($_POST["client_name"]);
    $client_email  = trim($_POST["client_email"] ?? "");
    $attorney_name = trim($_POST["attorney_name"]);
    $title         = trim($_POST["title"]);
    $description   = trim($_POST["description"]);
    $location      = trim($_POST["location"]);
    $status        = $_POST["status"];

    $start_time = str_replace("T"," ",$_POST["start_time"]);
    $end_time   = str_replace("T"," ",$_POST["end_time"]);

    $timezone = trim($_POST["timezone"]);
    $type     = $_POST["type"];

    if ($client_name==="" || $attorney_name==="" || $title==="" || $start_time==="" || $end_time==="") {
        $msg = "Fill in all required fields.";
    }
    elseif (strtotime($end_time) <= strtotime($start_time)) {
        $msg = "End time must be after start time.";
    }
    else {

        $stmt1 = $conn->prepare(
            "INSERT INTO appointments
            (client_name, client_email, attorney_name, title, description, location, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt1->bind_param(
            "sssssss",
            $client_name,
            $client_email,
            $attorney_name,
            $title,
            $description,
            $location,
            $status
        );

        if ($stmt1->execute()) {

            $appointment_id = $conn->insert_id;

            $stmt2 = $conn->prepare(
                "INSERT INTO appointment_times
                (appointment_id, start_time, end_time, timezone, type)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt2->bind_param(
                "issss",
                $appointment_id,
                $start_time,
                $end_time,
                $timezone,
                $type
            );

            if ($stmt2->execute()) {
                $msg = "✅ Meeting created successfully!";
            } else {
                $msg = "❌ Error saving time.";
            }

            $stmt2->close();
        }
        else {
            $msg = "❌ Error creating meeting.";
        }

        $stmt1->close();
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create Meeting</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

<div class="nav">
  <div>
    <strong>Create Meeting</strong>
    <div class="small">Schedule a consultation</div>
  </div>
  <div class="actions">
    <a class="btn btn-light" href="list_meetings.php">Meetings</a>
    <a class="btn btn-red" href="logout.php">Logout</a>
  </div>
</div>

<div class="card">

<?php if ($msg): ?>
  <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<form method="POST">

  <label>Client Name *</label>
  <input name="client_name" required>

  <label>Client Email</label>
  <input type="email" name="client_email">

  <label>Attorney Name *</label>
  <input name="attorney_name" required>

  <label>Title *</label>
  <input name="title" required>

  <label>Description</label>
  <textarea name="description"></textarea>

  <label>Location</label>
  <input name="location">

  <label>Start Time *</label>
  <input type="datetime-local" name="start_time" required>

  <label>End Time *</label>
  <input type="datetime-local" name="end_time" required>

  <label>Status</label>
  <select name="status">
    <option value="scheduled">scheduled</option>
    <option value="confirmed">confirmed</option>
    <option value="completed">completed</option>
    <option value="cancelled">cancelled</option>
    <option value="no_show">no_show</option>
  </select>

  <label>Type</label>
  <select name="type">
    <option value="meeting">meeting</option>
    <option value="call">call</option>
    <option value="court">court</option>
    <option value="deadline">deadline</option>
    <option value="other">other</option>
  </select>

  <label>Timezone</label>
  <input name="timezone" value="America/New_York">

  <button class="btn btn-dark" style="margin-top:14px;">
    Create Meeting
  </button>

</form>

</div>
</div>

</body>
</html>