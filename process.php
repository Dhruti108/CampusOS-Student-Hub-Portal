<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $form_type = $_POST["form_type"] ?? "";

    if ($form_type == "registration") {

        require "db.php";

        $firstname = trim($_POST["firstname"] ?? "");
        $lastname = trim($_POST["lastname"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $enrollment = trim($_POST["enrollment"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if ($firstname == "" || $lastname == "" || $email == "" ||
            $enrollment == "" || $password == "" || $confirm_password == "") {
            exit("Error: Please fill all required fields.");
        }

        if (!preg_match("/^[A-Za-z ]+$/", $firstname) ||
            !preg_match("/^[A-Za-z ]+$/", $lastname)) {
            exit("Error: Enter a valid name.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            exit("Error: Enter a valid email address.");
        }

        if (!preg_match("/^[A-Za-z0-9]+$/", $enrollment)) {
            exit("Error: Enter a valid enrollment ID.");
        }

        if (!preg_match("/^(?=.*[A-Za-z])(?=.*[0-9]).{6,}$/", $password)) {
            exit("Error: Password must contain letters and numbers and be at least 6 characters.");
        }

        if ($password !== $confirm_password) {
            exit("Error: Passwords do not match.");
        }

        $check = $conn->prepare(
            "SELECT student_id FROM students WHERE email = ? OR enrollment = ?"
        );

        if (!$check) {
            $conn->close();
            exit("Error: Unable to check registration details.");
        }

        $check->bind_param("ss", $email, $enrollment);

        if (!$check->execute()) {
            $check->close();
            $conn->close();
            exit("Error: Unable to check registration details.");
        }

        $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            $conn->close();

            exit("<h2>Registration Failed</h2>
                  <p>Email or enrollment ID already exists.</p>
                  <a href='register.html'>Try Again</a>");
        }

        $check->close();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO students (firstname, lastname, email, enrollment, password)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $conn->close();
            exit("Error: Unable to prepare registration.");
        }

        $stmt->bind_param(
            "sssss",
            $firstname,
            $lastname,
            $email,
            $enrollment,
            $hashed_password
        );

        try {
            if ($stmt->execute()) {
                echo "<h2>Registration Successful!</h2>";
                echo "<p>Your CampusOS account has been created successfully.</p>";
                echo "<a href='register.html'>Back to Registration</a>";
            } else {
                echo "<h2>Registration Failed</h2>";
                echo "<p>Please try again later.</p>";
                echo "<a href='register.html'>Try Again</a>";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                echo "<h2>Registration Failed</h2>";
                echo "<p>Email or enrollment ID already exists.</p>";
                echo "<a href='register.html'>Try Again</a>";
            } else {
                echo "<h2>Registration Failed</h2>";
                echo "<p>Please try again later.</p>";
                echo "<a href='register.html'>Try Again</a>";
            }
        }

        $stmt->close();
        $conn->close();

    } elseif ($form_type == "contact") {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $studentid = trim($_POST["studentid"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $message = trim($_POST["message"] ?? "");

        if ($name == "" || $email == "" || $studentid == "" ||
            $category == "" || $message == "") {
            exit("Error: Please fill all required fields.");
        }

        if (!preg_match("/^[A-Za-z ]+$/", $name)) {
            exit("Error: Enter a valid name.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            exit("Error: Enter a valid email address.");
        }

        $file = fopen("contacts.csv", "a");

        if ($file === false) {
            exit("Error: Unable to save your message.");
        }

        fputcsv($file, [
            $name,
            $email,
            $studentid,
            $category,
            $message,
            date("Y-m-d H:i:s")
        ]);

        fclose($file);

        echo "<h2>Message Sent Successfully!</h2>";
        echo "<p>Thank you for contacting CampusOS. We will get back to you soon.</p>";
        echo "<a href='contact.html'>Back to Contact Page</a>";

    } else {
        echo "Error: Invalid form submission.";
    }

} else {
    echo "Invalid request.";
}

?>
