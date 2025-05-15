<?php
$servername = "localhost";  // Change this to your database server's hostname or IP address
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

session_start(); // Start the session

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST["action"]) && $_POST["action"] === "delete" && isset($_POST["record_id"], $_POST["uid"])) {
    $record_id = mysqli_real_escape_string($connect, $_POST["record_id"]);
    $uid = mysqli_real_escape_string($connect, $_POST["uid"]);
    
    $deleteQuery = "DELETE FROM manual_timesheet WHERE id = $record_id AND staff_id = $uid";
    
    if (mysqli_query($connect, $deleteQuery)) {
        // Deletion was successful
        echo json_encode(array('status' => 'success', 'message' => 'Entry deleted successfully'));
    } else {
        // Handle the case where deletion failed
        echo json_encode(array('status' => 'error', 'message' => 'Error deleting entry'));
    }

    if (isset($_POST["action"]) && $_POST["action"] === "edit" && isset($_POST["record_id"], $_POST["rosteredTotalHours"], $_POST["clientTotalHours"], $_POST["comment"])) {
        $record_id = mysqli_real_escape_string($connect, $_POST["record_id"]);
        $rosteredTotalHours = mysqli_real_escape_string($connect, $_POST["rosteredTotalHours"]);
        $clientTotalHours = mysqli_real_escape_string($connect, $_POST["clientTotalHours"]);
        $comment = mysqli_real_escape_string($connect, $_POST["comment"]);
        $division = mysqli_real_escape_string($connect, $_POST["division"]);

        
        // Update the data in the "manual_timesheet" table for the specified record_id
        $query = "UPDATE manual_timesheet SET rosteredTotalHours = '$rosteredTotalHours', clientTotalHours = '$clientTotalHours', comment = '$comment', division  ='$division' WHERE id = $record_id";
        if (mysqli_query($connect, $query)) {
            // Successful edit
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "message" => "Error: " . mysqli_error($connect)));
        }
    }

} elseif (isset($_POST["rosteredTotalHours"], $_POST["clientTotalHours"], $_POST["comment"], $_POST["uid"], $_POST["clientName"])) {
    $rosteredTotalHours = mysqli_real_escape_string($connect, $_POST["rosteredTotalHours"]);
    $clientTotalHours = mysqli_real_escape_string($connect, $_POST["clientTotalHours"]);
    $comment = mysqli_real_escape_string($connect, $_POST["comment"]);
    $uid = mysqli_real_escape_string($connect, $_POST["uid"]);
    $dateCreated = mysqli_real_escape_string($connect, $_POST["dateCreated"]);
    $dateUpdated = mysqli_real_escape_string($connect, $_POST["dateUpdated"]);
    $clientName = mysqli_real_escape_string($connect, $_POST["clientName"]);
    $division = mysqli_real_escape_string($connect, $_POST["division"]);

    // Combine existing comment with clientName value
    $comment = "Client Name: " . $clientName . ", " . $comment;

    // Insert the data into the "manual_timesheet" table with the provided user_id and updated comment
    $insertQuery = "INSERT INTO manual_timesheet (rosteredTotalHours, clientTotalHours, comment, staff_id, division) VALUES ('$rosteredTotalHours', '$clientTotalHours', '$comment', '$uid','$division')";

    if (mysqli_query($connect, $insertQuery)) {
        // Insertion was successful
        echo json_encode(array('status' => 'success', 'message' => 'Entry added successfully'));
    } else {
        // Handle the case where insertion failed
        echo json_encode(array('status' => 'error', 'message' => 'Error adding entry'));
    }
} else {
    // Handle the case where some POST data is missing
    echo json_encode(array('status' => 'error', 'message' => 'Missing required data in the POST request'));
}
mysqli_close($connect);

?>
