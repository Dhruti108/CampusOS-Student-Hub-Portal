
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

require_once "db.php";

$student_id = (int) $_SESSION["student_id"];

$stmt = $conn->prepare(
    "SELECT firstname, lastname, email, enrollment
     FROM students
     WHERE student_id = ?"
);

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$events = [];
$eventResult = $conn->query(
    "SELECT event_name, event_date, location, description
     FROM events
     ORDER BY event_date ASC
     LIMIT 5"
);

if ($eventResult) {
    while ($event = $eventResult->fetch_assoc()) {
        $events[] = $event;
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampusOS</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>
<header class="navbar">
    <div class="logo">
        <font size="5"><i>CampusOS</i></font>
    </div>

    <nav>
        <a href="index.html">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="academics.html">Academics</a>
        <a href="campus-services.html">Campus Services</a>
        <a href="faq.html">FAQs</a>
        <a href="contact.html">Contact</a>
        <a href="about.html">About</a>
        <a href="feedback.html">Feedback</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <section class="dashboard-header">
        <div>
            <h1>Welcome Back, <?php echo e($student["firstname"]); ?>!</h1>
            <p>Here's an overview of your academic progress and campus activities.</p>
        </div>

        <div class="student-profile">
            <h2>Student Profile</h2>

            <div class="profile-info">
                <div>
                    <b>Name</b>
                    <p><?php echo e($student["firstname"] . " " . $student["lastname"]); ?></p>
                </div>

                <div>
                    <b>Enrollment No.</b>
                    <p><?php echo e($student["enrollment"]); ?></p>
                </div>

                <div>
                    <b>Email</b>
                    <p><?php echo e($student["email"]); ?></p>
                </div>

                <div>
                    <b>Branch</b>
                    <p>Computer Science Engineering</p>
                </div>

                <div>
                    <b>Semester</b>
                    <p>3rd Semester</p>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-stats">
        <div class="stat-card">
            <h3>CGPA</h3>
            <p>8.2</p>
        </div>

        <div class="stat-card">
            <h3>Percentile</h3>
            <p>92.4%</p>
        </div>

        <div class="stat-card">
            <h3>Attendance</h3>
            <p>86%</p>
        </div>

        <div class="stat-card">
            <h3>Courses</h3>
            <p>5</p>
        </div>

        <div class="stat-card">
            <h3>Assignments Due</h3>
            <p>2</p>
        </div>
    </section>

    <section class="academic-section">
        <h2>Academic Performance</h2>

        <table width="100%" border="1" cellpadding="15">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Internal Marks</th>
                    <th>External Marks</th>
                    <th>Total</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Data Structures</td>
                    <td>38</td>
                    <td>52</td>
                    <td>90</td>
                    <td>A+</td>
                </tr>
                <tr>
                    <td>Java Programming</td>
                    <td>36</td>
                    <td>49</td>
                    <td>85</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>Database Management</td>
                    <td>35</td>
                    <td>47</td>
                    <td>82</td>
                    <td>A</td>
                </tr>
                <tr>
                    <td>Computer Networks</td>
                    <td>34</td>
                    <td>45</td>
                    <td>79</td>
                    <td>B+</td>
                </tr>
                <tr>
                    <td>Web Development</td>
                    <td>39</td>
                    <td>51</td>
                    <td>90</td>
                    <td>A+</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="dashboard-content">
        <div class="dashboard-card">
            <h2>Announcements</h2>
            <p>Hackathon Registration is now open.</p>
            <p>Library will remain closed on Friday.</p>
            <p>Mid-Semester examination schedule has been released.</p>

            <button type="button" onclick="showAnnouncements()">
                View All Announcements
            </button>

            <div id="announcements"></div>
        </div>

        <div class="dashboard-card">
            <h2>Upcoming Events</h2>

            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-item">
                        <p><b><?php echo e($event["event_name"]); ?></b></p>
                        <p>
                            Date:
                            <?php
                            echo e(date(
                                "d M Y",
                                strtotime($event["event_date"])
                            ));
                            ?>
                        </p>

                        <?php if (!empty($event["location"])): ?>
                            <p>Location: <?php echo e($event["location"]); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($event["description"])): ?>
                            <p><?php echo e($event["description"]); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No events are available.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="dashboard-content">
        <div class="dashboard-card">
            <h2>Assignments</h2>
            <p><b>Java Assignment</b> - Due 20 September</p>
            <p><b>Web Development Project</b> - Due 25 September</p>
            <p><b>Data Structures Practical</b> - Completed</p>
        </div>

        <div class="dashboard-card">
            <h2>Quick Access</h2>
            <p><a href="academics.html">View Academics</a></p>
            <p><a href="campus-services.html">Campus Services</a></p>
            <p><a href="faq.html">Student Support</a></p>
            <p><a href="feedback.html">Give Feedback</a></p>
        </div>
    </section>

    <section class="attendance-section">
        <h2>Attendance Overview</h2>

        <table width="100%" border="1" cellpadding="15">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Classes Attended</th>
                    <th>Total Classes</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Data Structures</td>
                    <td>28</td>
                    <td>30</td>
                    <td>93%</td>
                </tr>
                <tr>
                    <td>Java Programming</td>
                    <td>29</td>
                    <td>30</td>
                    <td>97%</td>
                </tr>
                <tr>
                    <td>Computer Networks</td>
                    <td>27</td>
                    <td>30</td>
                    <td>90%</td>
                </tr>
                <tr>
                    <td>Web Development</td>
                    <td>29</td>
                    <td>30</td>
                    <td>97%</td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

<footer>
    <h3>CampusOS</h3>
    <p>Your One-Stop Student Hub Portal</p>

    <p>
        <a href="index.html">Home</a> |
        <a href="about.html">About</a> |
        <a href="contact.html">Contact</a> |
        <a href="feedback.html">Feedback</a> |
        <a href="logout.php">Logout</a>
    </p>

    <p>© 2026 CampusOS Student Hub Portal | All Rights Reserved</p>
</footer>
</body>
</html>
