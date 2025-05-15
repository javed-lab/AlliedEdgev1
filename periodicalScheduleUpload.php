<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

session_start(); // Start the session

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$task_id = null; // Initialize task_id

// Check if task_id is in cookies
if (isset($_COOKIE['task_id'])) {
    $task_id = $_COOKIE['task_id'];
} else {
    // Handle the case when the task_id is not found in cookies
    echo "Task ID cookie not found.";
    exit;
}

// Now $task_id contains the task_id from cookies, and you can use it in your code.
echo "Task ID from cookie: " . $task_id;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file_scheduling"])) {
    $file = $_FILES["csv_file_scheduling"]["tmp_name"];

    if (($handle = fopen($file, "r")) !== false) {
        $query = "INSERT INTO task_schedules (task_id, status_id, date, date_completed) VALUES (?, ?, ?, ?)";

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $status_id = $data[0]; // Assuming status_id is in the first column
            $date = $data[1]; // Assuming date is in the second column
            $date_completed = $data[2]; // Assuming date_completed is in the third column
            $stmt = mysqli_prepare($connect, $query);
            if (!$stmt) {
                die("Error preparing the SQL statement: " . mysqli_error($connect));
            }

            // Bind the data, including the retrieved task_id from cookies
            mysqli_stmt_bind_param($stmt, "ssss", $task_id, $status_id, $date, $date_completed);

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Data inserted successfully
                echo "Data inserted successfully.<br>";
            } else {
                // Handle the case when data insertion fails
                echo "Error: " . mysqli_error($connect) . "<br>";
            }

            mysqli_stmt_close($stmt);
        }

        // Close the database connection
        mysqli_close($connect);
        fclose($handle);
        setcookie("task_id", "", time() - 3600, "/");

    } else {
        echo "Error opening the CSV file.<br>";
    }
} else {
    echo "No CSV file was uploaded or the POST request is missing.<br>";
}


?>
