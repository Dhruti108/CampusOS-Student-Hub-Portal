<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["role"] ?? "student") !== "student") {
    header("Location: admin-dashboard.php");
    exit();
}
?>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION["firstname"]); ?>!</h1>
<h2>Student Dashboard</h2>
<a href="logout.php">Logout</a>
