<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/config/auth.php";
require_login();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { exit("Invalid id"); }

$stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE appointment_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list_meetings.php");
exit;