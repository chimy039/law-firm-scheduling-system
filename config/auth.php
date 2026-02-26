<?php
declare(strict_types=1);
session_start();

function require_login(): void {
  if (empty($_SESSION["user"])) {
    header("Location: login.php");
    exit;
  }
}

function current_user(): array {
  return $_SESSION["user"] ?? [];
}

function is_role(string $role): bool {
  return !empty($_SESSION["user"]) && ($_SESSION["user"]["role"] === $role);
}

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}