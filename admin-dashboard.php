<?php
session_start();
require "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION["role"] ?? "student";

if ($role !== "admin") {
    header("Location: student-dashboard.php");
    exit();
}

$studentCount = 0;
$eventCount = 0;
$latestStudents = [];

$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result) {
    $studentCount = $result->fetch_assoc()["total"];
    $result->free();
}

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $eventCount = $result->fetch_assoc()["total"];
    $result->free();
}

$result = $conn->query(
    "SELECT student_id, firstname, lastname, email, enrollment
     FROM students
     ORDER BY student_id DESC
     LIMIT 5"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestStudents[] = $row;
    }
    $result->free();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CampusOS</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            padding: 25px;
            background: #fff7f9;
        }

        .dashboard {
            max-width: 1100px;
            margin: auto;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #ead3dc;
        }

        .stat-card h2 {
            color: #8f3453;
        }

        .menu a {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            background: #8f3453;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #e5c7d2;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #6d1f3a;
            color: white;
        }
    </style>
</head>

<body>
<div class="dashboard">

    <h1>Welcome, <?php echo e($_SESSION["firstname"] ?? "Admin"); ?>!</h1>
    <h2>CampusOS Admin Dashboard</h2>
    <p>Role: <?php echo e($role); ?></p>

    <h3>Dashboard Menu</h3>

    <div class="menu">
        <?php if ($role === "admin") { ?>
            <a href="student-management.php">Manage Students</a>
            <a href="event-management.php">Manage Events</a>
            <a href="events-api.html">Events API</a>
        <?php } else { ?>
            <a href="student-dashboard.php">Student Dashboard</a>
        <?php } ?>

        <a href="logout.php">Logout</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h3>Total Students</h3>
            <h2><?php echo (int)$studentCount; ?></h2>
        </div>

        <div class="stat-card">
            <h3>Total Events</h3>
            <h2><?php echo (int)$eventCount; ?></h2>
        </div>
    </div>

    <h2>Latest Student Registrations</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Enrollment</th>
        </tr>

        <?php if (count($latestStudents) > 0) { ?>
            <?php foreach ($latestStudents as $student) { ?>
                <tr>
                    <td><?php echo (int)$student["student_id"]; ?></td>
                    <td><?php echo e($student["firstname"]); ?></td>
                    <td><?php echo e($student["lastname"]); ?></td>
                    <td><?php echo e($student["email"]); ?></td>
                    <td><?php echo e($student["enrollment"]); ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5">No student registrations found.</td>
            </tr>
        <?php } ?>
    </table>

</div>
</body>
</html>

<?php
$conn->close();
?>
