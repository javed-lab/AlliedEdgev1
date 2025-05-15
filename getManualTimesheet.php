<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["uid"]) && !empty($_POST["uid"])) {
        $uid = mysqli_real_escape_string($connect, $_POST["uid"]);

        // Check if 'uid' is a valid format (if needed, based on your requirements)
        // Example: if (!preg_match('/^[a-zA-Z0-9]+$/', $uid)) { /* Handle invalid 'uid' */ }

        if (isset($_POST["action"])) {
            $action = mysqli_real_escape_string($connect, $_POST["action"]);

            if ($action === "delete") {
                if (isset($_POST["record_id"]) && !empty($_POST["record_id"])) {
                    $record_id = mysqli_real_escape_string($connect, $_POST["record_id"]);
                    $query = "DELETE FROM manual_timesheet WHERE id = '$record_id' AND staff_id = '$uid'";
                    $result = mysqli_query($connect, $query);

                    if ($result) {
                        echo json_encode(["message" => "Record deleted successfully."]);
                    } else {
                        echo json_encode(["error" => "Error deleting record: " . mysqli_error($connect)]);
                    }
                    exit;
                } else {
                    echo json_encode(["error" => "Missing or empty 'record_id' parameter for delete."]);
                    exit;
                }
            } elseif ($action === "update") {
                // Similar checks for other actions can be added here

                // Example checks for 'update':
                if (isset($_POST["record_id"]) && !empty($_POST["record_id"]) && isset($_POST["rosteredTotalHours"]) && !empty($_POST["rosteredTotalHours"])) {
                    // Rest of your 'update' logic
                } else {
                    echo json_encode(["error" => "Missing or empty parameters for update."]);
                    exit;
                }
            } else {
                echo json_encode(["error" => "Invalid action."]);
                exit;
            }
        } else {
            // Fetch all records for the specific staff_id and uid
            $query = "SELECT id, rosteredTotalHours, clientTotalHours, division, dateCreated, dateUpdated, (clientTotalHours - rosteredTotalHours) AS difference FROM manual_timesheet WHERE staff_id = '$uid'";
            $result = mysqli_query($connect, $query);

            if ($result) {
                $data = array();

                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }

                echo json_encode($data);
            } else {
                echo json_encode(["error" => "Query error: " . mysqli_error($connect)]);
            }
        }
    } else {
        echo json_encode(["error" => "Missing or empty 'uid' parameter."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}

mysqli_close($connect);

?>
