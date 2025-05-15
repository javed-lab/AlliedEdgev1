<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";


$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Insertion Logic
if (isset($_POST["uid"], $_POST["clientTotalHours"])) {
    $uid = mysqli_real_escape_string($connect, $_POST["uid"]);
    $clientTotalHours = mysqli_real_escape_string($connect, $_POST["clientTotalHours"]);
    $clientName = mysqli_real_escape_string($connect, $_POST["clientName"]);
    // Prepare the current timestamp for dateCreated and dateUpdated
    $currentDateTime = date('Y-m-d H:i:s');
    $division = mysqli_real_escape_string($connect, $_POST["division"]);

    // Insert data into the database
    $insertQuery = "INSERT INTO contractedhours (clientTotalHours, dateCreated, dateUpdated, staff_id, clientName, division) 
                    VALUES ('$clientTotalHours', '$currentDateTime', '$currentDateTime', '$uid', '$clientName', '$division')";

    if (mysqli_query($connect, $insertQuery)) {
        echo json_encode(array('status' => 'success', 'message' => 'Entry added successfully'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Error adding entry: ' . mysqli_error($connect)));
    }
} 

// Deletion Logic
elseif (isset($_POST["uid"], $_POST["record_id"], $_POST["clientTotalHours"])) {
    $uid = mysqli_real_escape_string($connect, $_POST["uid"]);
    $record_id = mysqli_real_escape_string($connect, $_POST["record_id"]);
    $clientTotalHours = mysqli_real_escape_string($connect, $_POST["clientTotalHours"]);
    $clientName = mysqli_real_escape_string($connect, $_POST["clientName"]);

    // Delete the record based on provided parameters
    $deleteQuery = "DELETE FROM contractedhours WHERE staff_id = '$uid' AND record_id = '$record_id' AND clientTotalHours = '$clientTotalHours' AND clientName = '$clientName'";

    if (mysqli_query($connect, $deleteQuery)) {
        echo json_encode(array('status' => 'success', 'message' => 'Record deleted successfully'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to delete record: ' . mysqli_error($connect)));
    }
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Missing required data in the POST request'));
}

mysqli_close($connect);
?>
