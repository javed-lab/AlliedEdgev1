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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"]["tmp_name"];
    
    if (($handle = fopen($file, "r")) !== false) {
        $query = "INSERT INTO tasks (division_id, site_id, date, frequency_id, description) VALUES (?, ?, ?, ?, ?)";

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $stmt = mysqli_prepare($connect, $query);
            if (!$stmt) {
                die("Error preparing the SQL statement: " . mysqli_error($connect));
            }

            // Bind the data
            mysqli_stmt_bind_param($stmt, "sssss", $data[0], $data[1], $data[2], $data[3], $data[4]);

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Get the last inserted task ID
                $task_id = mysqli_insert_id($connect);
            
                // Store the task_id in a cookie
                setcookie("task_id", $task_id, time() + 3600, "/"); // Set a cookie named "task_id"
            
                echo "Data inserted successfully. Task ID: $task_id<br>";
                
                // Add a success message
                echo "Upload successful!";
            } else {
                // Handle the case when data insertion fails
                echo "Error: " . mysqli_error($connect) . "<br>";
            }

            mysqli_stmt_close($stmt);
        }

        // Close the database connection
        mysqli_close($connect);
        fclose($handle);
    } else {
        echo "Error opening the CSV file.<br>";
    }
} else {
    echo "No CSV file was uploaded or the POST request is missing.<br>";
}
?>
