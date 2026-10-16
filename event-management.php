<?php
session_start();
require "db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["role"] ?? "") !== "admin") {
    die("Access denied. Only administrators can manage events.");
}

$message = "";
$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function uploadPoster($file, $uploadDir) {
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return ["", ""];
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return [null, "Poster upload failed."];
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        return [null, "Poster must be 2 MB or smaller."];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime])) {
        return [null, "Only JPG, PNG, and WebP posters are allowed."];
    }

    $imageInfo = getimagesize($file["tmp_name"]);

    if ($imageInfo === false) {
        return [null, "The uploaded file is not a valid image."];
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];

    if (!move_uploaded_file(
        $file["tmp_name"],
        $uploadDir . $filename
    )) {
        return [null, "Could not save the poster."];
    }

    return [$filename, ""];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $eventName = trim($_POST["event_name"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventId = (int)($_POST["event_id"] ?? 0);

    if ($action === "delete") {

        $stmt = $conn->prepare(
            "SELECT poster FROM events WHERE event_id = ?"
        );
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $stmt->bind_result($oldPoster);
        $exists = $stmt->fetch();
        $stmt->close();

        if (!$exists) {
            $message = "Event not found.";
        } else {
            $stmt = $conn->prepare(
                "DELETE FROM events WHERE event_id = ?"
            );
            $stmt->bind_param("i", $eventId);

            if ($stmt->execute()) {
                if ($oldPoster) {
                    $oldPath = $uploadDir . basename($oldPoster);
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $message = "Event deleted successfully.";
            } else {
                $message = "Failed to delete event.";
            }

            $stmt->close();
        }

    } elseif ($action === "add" || $action === "update") {

        if ($eventName === "" || $eventDate === "" || $location === "") {
            $message = "Event name, date, and location are required.";
        } elseif (
            !preg_match("/^\d{4}-\d{2}-\d{2}$/", $eventDate) ||
            !checkdate(
                (int)substr($eventDate, 5, 2),
                (int)substr($eventDate, 8, 2),
                (int)substr($eventDate, 0, 4)
            )
        ) {
            $message = "Enter a valid event date.";
        } else {

            $newPoster = "";
            $uploadError = "";

            if (isset($_FILES["poster"]) &&
                $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
                [$newPoster, $uploadError] =
                    uploadPoster($_FILES["poster"], $uploadDir);
            }

            if ($uploadError !== "") {
                $message = $uploadError;
            } else {

                if ($action === "add") {

                    $stmt = $conn->prepare(
                        "INSERT INTO events
                        (event_name, event_date, location, description, poster)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    $posterValue = $newPoster !== "" ? $newPoster : null;

                    $stmt->bind_param(
                        "sssss",
                        $eventName,
                        $eventDate,
                        $location,
                        $description,
                        $posterValue
                    );

                    if ($stmt->execute()) {
                        $message = "Event added successfully.";
                    } else {
                        $message = "Failed to add event.";
                        if ($newPoster !== "" &&
                            is_file($uploadDir . $newPoster)) {
                            unlink($uploadDir . $newPoster);
                        }
                    }

                    $stmt->close();

                } else {

                    $stmt = $conn->prepare(
                        "SELECT poster FROM events WHERE event_id = ?"
                    );
                    $stmt->bind_param("i", $eventId);
                    $stmt->execute();
                    $stmt->bind_result($oldPoster);
                    $exists = $stmt->fetch();
                    $stmt->close();

                    if (!$exists) {
                        $message = "Event not found.";
                    } else {
                        if ($newPoster !== "") {
                            $stmt = $conn->prepare(
                                "UPDATE events
                                 SET event_name=?, event_date=?, location=?,
                                     description=?, poster=?
                                 WHERE event_id=?"
                            );
                            $stmt->bind_param(
                                "sssssi",
                                $eventName,
                                $eventDate,
                                $location,
                                $description,
                                $newPoster,
                                $eventId
                            );
                        } else {
                            $stmt = $conn->prepare(
                                "UPDATE events
                                 SET event_name=?, event_date=?, location=?,
                                     description=?
                                 WHERE event_id=?"
                            );
                            $stmt->bind_param(
                                "ssssi",
                                $eventName,
                                $eventDate,
                                $location,
                                $description,
                                $eventId
                            );
                        }

                        if ($stmt->execute()) {
                            $message = "Event updated successfully.";

                            if ($newPoster !== "" && $oldPoster) {
                                $oldPath = $uploadDir . basename($oldPoster);
                                if (is_file($oldPath)) {
                                    unlink($oldPath);
                                }
                            }
                        } else {
                            $message = "Failed to update event.";
                            if ($newPoster !== "" &&
                                is_file($uploadDir . $newPoster)) {
                                unlink($uploadDir . $newPoster);
                            }
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
}

$editEvent = null;

if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT event_id, event_name, event_date, location,
                description, poster
         FROM events WHERE event_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result(
        $eId, $eName, $eDate, $eLocation, $eDescription, $ePoster
    );

    if ($stmt->fetch()) {
        $editEvent = [
            "event_id" => $eId,
            "event_name" => $eName,
            "event_date" => $eDate,
            "location" => $eLocation,
            "description" => $eDescription,
            "poster" => $ePoster
        ];
    }

    $stmt->close();
}

$result = $conn->query(
    "SELECT event_id, event_name, event_date, location, description, poster
     FROM events ORDER BY event_date DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Event Management - CampusOS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { padding: 25px; }
        .event-form input, .event-form textarea, .event-form button {
            padding: 10px;
            margin: 6px 0;
            width: 100%;
            box-sizing: border-box;
        }
        .event-form button, .action-button {
            background: #8f3453;
            color: white;
            border: 0;
            padding: 9px 13px;
            cursor: pointer;
            border-radius: 5px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #6d1f3a; color: white; }
        .message { font-weight: bold; color: #6d1f3a; }
        .poster { width: 100px; max-height: 100px; object-fit: contain; }
        .event-form { max-width: 600px; }
    </style>
</head>
<body>

<h1>CampusOS Event Management</h1>

<?php if ($message !== "") { ?>
    <p class="message"><?php echo clean($message); ?></p>
<?php } ?>

<h2><?php echo $editEvent ? "Update Event" : "Add New Event"; ?></h2>

<form class="event-form" method="POST"
      action="event-management.php" enctype="multipart/form-data">

    <input type="hidden" name="action"
           value="<?php echo $editEvent ? "update" : "add"; ?>">

    <?php if ($editEvent) { ?>
        <input type="hidden" name="event_id"
               value="<?php echo (int)$editEvent["event_id"]; ?>">
    <?php } ?>

    <label>Event Name</label>
    <input type="text" name="event_name" maxlength="100" required
           value="<?php echo clean($editEvent["event_name"] ?? ""); ?>">

    <label>Event Date</label>
    <input type="date" name="event_date" required
           value="<?php echo clean($editEvent["event_date"] ?? ""); ?>">

    <label>Location</label>
    <input type="text" name="location" maxlength="100" required
           value="<?php echo clean($editEvent["location"] ?? ""); ?>">

    <label>Description</label>
    <textarea name="description" rows="4"><?php
        echo clean($editEvent["description"] ?? "");
    ?></textarea>

    <label>Event Poster (JPG, PNG, WebP; maximum 2 MB)</label>
    <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
    <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

    <?php if (!empty($editEvent["poster"])) { ?>
        <p>Current poster:</p>
        <img class="poster"
             src="uploads/<?php echo rawurlencode(basename($editEvent["poster"])); ?>"
             alt="Current event poster">
    <?php } ?>

    <button type="submit">
        <?php echo $editEvent ? "Update Event" : "Add Event"; ?>
    </button>

    <?php if ($editEvent) { ?>
        <a href="event-management.php">Cancel</a>
    <?php } ?>
</form>

<hr>

<h2>All Events</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Event</th>
        <th>Date</th>
        <th>Location</th>
        <th>Description</th>
        <th>Poster</th>
        <th>Actions</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($event = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo (int)$event["event_id"]; ?></td>
                <td><?php echo clean($event["event_name"]); ?></td>
                <td><?php echo clean($event["event_date"]); ?></td>
                <td><?php echo clean($event["location"]); ?></td>
                <td><?php echo clean($event["description"] ?? ""); ?></td>
                <td>
                    <?php if (!empty($event["poster"])) { ?>
                        <a href="uploads/<?php
                            echo rawurlencode(basename($event["poster"]));
                        ?>" target="_blank">
                            <img class="poster" src="uploads/<?php
                                echo rawurlencode(basename($event["poster"]));
                            ?>" alt="Event poster">
                        </a>
                    <?php } else { ?>
                        No poster
                    <?php } ?>
                </td>
                <td>
                    <a href="event-management.php?edit=<?php
                        echo (int)$event["event_id"];
                    ?>">Edit</a>

                    <form method="POST" action="event-management.php"
                          onsubmit="return confirm('Delete this event?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="event_id"
                               value="<?php echo (int)$event["event_id"]; ?>">
                        <button class="action-button" type="submit">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr><td colspan="7">No events found.</td></tr>
    <?php } ?>
</table>

<br>
<a href="admin-dashboard.php">Back to Admin Dashboard</a> |
<a href="logout.php">Logout</a>

</body>
</html>

<?php
if ($result) {
    $result->free();
}
$conn->close();
?>
