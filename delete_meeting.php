<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/config/auth.php";
require_login();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { exit("Invalid id"); }

$stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: list_meetings.php");
exit;