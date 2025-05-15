<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

// Create a database connection
$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the record ID is provided via POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["record_id"])) {
    $recordId = $_POST["record_id"];

    // Prepare an SQL query to delete the record
    $query = "DELETE FROM tasks WHERE id = ?";

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($connect, $query);
    if (!$stmt) {
        die("Error preparing the SQL statement: " . mysqli_error($connect));
    }

    // Bind the record ID
    mysqli_stmt_bind_param($stmt, "i", $recordId);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Record deleted successfully
        http_response_code(200); // Set the status code to 200 OK
        echo "Record deleted successfully.";
    } else {
        // Handle the case when record deletion fails
        http_response_code(500); // Set the status code to 500 Internal Server Error
        echo "Error: " . mysqli_error($connect);
    }

    mysqli_stmt_close($stmt);
} else {
    http_response_code(400); // Set the status code to 400 Bad Request
    echo "Invalid request. Please provide a record ID.";
}

// Close the database connection
mysqli_close($connect);
?>
