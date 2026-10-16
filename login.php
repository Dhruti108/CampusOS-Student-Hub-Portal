
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email == "" || $password == "") {
        $error = "Please enter email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT student_id, firstname, email, password, role
             FROM students WHERE email = ?"
        );

        if ($stmt) {

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {

                $stmt->bind_result(
                    $student_id,
                    $firstname,
                    $db_email,
                    $hashed_password,
                    $role
                );

                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {

                    session_regenerate_id(true);

                    $_SESSION["student_id"] = $student_id;
                    $_SESSION["firstname"] = $firstname;
                    $_SESSION["email"] = $db_email;
                    $_SESSION["role"] = $role;
                    $_SESSION["last_activity"] = time();

                    $stmt->close();
                    $conn->close();

                    if ($role == "admin") {
    header("Location: admin-dashboard.php");
} else {
    header("Location: dashboard.php");
}

                    exit();

                } else {
                    $error = "Invalid email or password.";
                }

            } else {
                $error = "Invalid email or password.";
            }

            $stmt->close();

        } else {
            $error = "Unable to process login.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - CampusOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>CampusOS Login</h1>

    <?php if ($error != "") { ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php } ?>

    <form action="login.php" method="POST">

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required>
        <br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required>
        <br><br>

        <input type="submit" value="Login">

    </form>

    <p>Don't have an account?
        <a href="register.html">Register here</a>
    </p>

</body>
</html>
