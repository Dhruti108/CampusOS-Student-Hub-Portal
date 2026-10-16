
<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["role"] ?? "student") === "admin") {
    header("Location: admin-dashboard.php");
    exit();
}

header("Location: dashboard.html");
exit();
?>
