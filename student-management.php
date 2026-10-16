<?php
session_start();
require "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["role"] ?? "") != "admin") {
    die("Access denied. Only administrators can manage students.");
}

$message = "";
$editStudent = null;

if (isset($_GET["msg"])) {
    if ($_GET["msg"] == "added") {
        $message = "Student added successfully!";
    } elseif ($_GET["msg"] == "updated") {
        $message = "Student updated successfully!";
    } elseif ($_GET["msg"] == "deleted") {
        $message = "Student deleted successfully!";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "";

    if ($action == "add" || $action == "update") {

        $firstname = trim($_POST["firstname"] ?? "");
        $lastname = trim($_POST["lastname"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $enrollment = trim($_POST["enrollment"] ?? "");
        $role = $_POST["role"] ?? "student";

        if (
            $firstname == "" || $lastname == "" ||
            $email == "" || $enrollment == ""
        ) {
            $message = "All fields are required.";
        } elseif (
            !preg_match("/^[A-Za-z ]+$/", $firstname) ||
            !preg_match("/^[A-Za-z ]+$/", $lastname)
        ) {
            $message = "Names must contain letters and spaces only.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Enter a valid email address.";
        } elseif (!preg_match("/^[A-Za-z0-9]+$/", $enrollment)) {
            $message = "Enrollment must contain letters and numbers only.";
        } elseif (!in_array($role, ["student", "admin"], true)) {
            $message = "Invalid role selected.";
        } else {

            if ($action == "add") {

                $password = password_hash(
                    bin2hex(random_bytes(16)),
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO students
                    (firstname, lastname, email, enrollment, password, role)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssss",
                    $firstname,
                    $lastname,
                    $email,
                    $enrollment,
                    $password,
                    $role
                );

            } else {

                $id = (int)($_POST["student_id"] ?? 0);

                $stmt = $conn->prepare(
                    "UPDATE students
                    SET firstname = ?, lastname = ?, email = ?,
                        enrollment = ?, role = ?
                    WHERE student_id = ?"
                );

                $stmt->bind_param(
                    "sssssi",
                    $firstname,
                    $lastname,
                    $email,
                    $enrollment,
                    $role,
                    $id
                );
            }

            if ($stmt) {
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();

                    header(
                        "Location: student-management.php?msg=" .
                        ($action == "add" ? "added" : "updated")
                    );
                    exit();
                } else {
                    if ($stmt->errno == 1062) {
                        $message = "Email or enrollment already exists.";
                    } else {
                        $message = "Operation failed. Please try again.";
                    }
                }

                $stmt->close();
            } else {
                $message = "Unable to prepare the database query.";
            }
        }
    }

    if ($action == "delete") {

        $id = (int)($_POST["student_id"] ?? 0);

        if ($id == (int)$_SESSION["student_id"]) {
            $message = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare(
                "DELETE FROM students WHERE student_id = ?"
            );
            $stmt->bind_param("i", $id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $conn->close();
                header("Location: student-management.php?msg=deleted");
                exit();
            } else {
                $message = "Student not found or deletion failed.";
            }

            $stmt->close();
        }
    }
}

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT student_id, firstname, lastname, email, enrollment, role
         FROM students WHERE student_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result(
        $editId, $editFirst, $editLast,
        $editEmail, $editEnrollment, $editRole
    );

    if ($stmt->fetch()) {
        $editStudent = [
            "student_id" => $editId,
            "firstname" => $editFirst,
            "lastname" => $editLast,
            "email" => $editEmail,
            "enrollment" => $editEnrollment,
            "role" => $editRole
        ];
    }

    $stmt->close();
}

$search = trim($_GET["search"] ?? "");
$filterRole = $_GET["role"] ?? "";

if (!in_array($filterRole, ["", "student", "admin"], true)) {
    $filterRole = "";
}

$sql = "SELECT student_id, firstname, lastname, email, enrollment, role
        FROM students
        WHERE (firstname LIKE ? OR lastname LIKE ?
        OR email LIKE ? OR enrollment LIKE ?)";

$searchTerm = "%" . $search . "%";

if ($filterRole != "") {
    $sql .= " AND role = ?";
}

$sql .= " ORDER BY student_id DESC";

$stmt = $conn->prepare($sql);

if ($filterRole != "") {
    $stmt->bind_param(
        "sssss",
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $filterRole
    );
} else {
    $stmt->bind_param(
        "ssss",
        $searchTerm, $searchTerm, $searchTerm, $searchTerm
    );
}

$stmt->execute();
$stmt->bind_result(
    $studentId, $firstName, $lastName,
    $studentEmail, $studentEnrollment, $studentRole
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management - CampusOS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            padding: 25px;
            font-family: Arial, sans-serif;
        }

        input, select, button {
            padding: 9px;
            margin: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #6d1f3a;
            color: white;
        }

        .message {
            color: #6d1f3a;
            font-weight: bold;
        }

        .delete-button {
            background: #b42335;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <h1>Student Management</h1>

    <p>Welcome, <?php echo htmlspecialchars($_SESSION["firstname"]); ?>!</p>

    <?php if ($message != "") { ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>

    <h2><?php echo $editStudent ? "Edit Student" : "Add Student"; ?></h2>

    <form method="POST" action="student-management.php">

        <input type="hidden" name="action"
               value="<?php echo $editStudent ? "update" : "add"; ?>">

        <?php if ($editStudent) { ?>
            <input type="hidden" name="student_id"
                   value="<?php echo $editStudent["student_id"]; ?>">
        <?php } ?>

        <input type="text" name="firstname" placeholder="First Name"
               maxlength="50" required
               value="<?php echo htmlspecialchars($editStudent["firstname"] ?? ""); ?>">

        <input type="text" name="lastname" placeholder="Last Name"
               maxlength="50" required
               value="<?php echo htmlspecialchars($editStudent["lastname"] ?? ""); ?>">

        <input type="email" name="email" placeholder="Email"
               maxlength="100" required
               value="<?php echo htmlspecialchars($editStudent["email"] ?? ""); ?>">

        <input type="text" name="enrollment" placeholder="Enrollment ID"
               maxlength="30" required
               value="<?php echo htmlspecialchars($editStudent["enrollment"] ?? ""); ?>">

        <select name="role">
            <option value="student"
                <?php echo (($editStudent["role"] ?? "student") == "student") ? "selected" : ""; ?>>
                Student
            </option>
            <option value="admin"
                <?php echo (($editStudent["role"] ?? "") == "admin") ? "selected" : ""; ?>>
                Admin
            </option>
        </select>

        <button type="submit">
            <?php echo $editStudent ? "Update Student" : "Add Student"; ?>
        </button>

        <?php if ($editStudent) { ?>
            <a href="student-management.php">Cancel</a>
        <?php } ?>
    </form>

    <hr>

    <h2>View Students</h2>

    <form method="GET" action="student-management.php">

        <input type="text" name="search" placeholder="Search students"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="role">
            <option value="">All Roles</option>
            <option value="student"
                <?php echo $filterRole == "student" ? "selected" : ""; ?>>
                Students
            </option>
            <option value="admin"
                <?php echo $filterRole == "admin" ? "selected" : ""; ?>>
                Admins
            </option>
        </select>

        <button type="submit">Search / Filter</button>
        <a href="student-management.php">Clear</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Enrollment</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>

        <?php
        $found = false;

        while ($stmt->fetch()) {
            $found = true;
        ?>
            <tr>
                <td><?php echo $studentId; ?></td>
                <td><?php echo htmlspecialchars($firstName); ?></td>
                <td><?php echo htmlspecialchars($lastName); ?></td>
                <td><?php echo htmlspecialchars($studentEmail); ?></td>
                <td><?php echo htmlspecialchars($studentEnrollment); ?></td>
                <td><?php echo htmlspecialchars($studentRole); ?></td>
                <td>
                    <a href="student-management.php?edit=<?php echo $studentId; ?>">
                        Edit
                    </a>

                    <form method="POST" action="student-management.php"
                          onsubmit="return confirm('Delete this student?');"
                          style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id"
                               value="<?php echo $studentId; ?>">
                        <button type="submit" class="delete-button">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>

        <?php if (!$found) { ?>
            <tr>
                <td colspan="7">No students found.</td>
            </tr>
        <?php } ?>
    </table>

    <br>
    <a href="admin-dashboard.php">Back to Admin Dashboard</a> |
    <a href="logout.php">Logout</a>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
