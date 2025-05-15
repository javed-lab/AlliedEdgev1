<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

// Establish the database connection
$connect = mysqli_connect($servername, $username, $password, $dbname);

// Check the connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

class FinesController {
    private $connect; // Class-level property to hold the database connection

    // Constructor to set the database connection
    public function __construct($connection) {
        $this->connect = $connection;
    }

    public function FinesRegister() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Assuming the data has been sanitized and validated
            $divisionId = $_POST['divisionId'];
            $nominatedPersonId = $_POST['nominatedPerson'];
            $position = $_POST['position'];
            $date = $_POST['date'];
            $rego = $_POST['rego'];
            $offenceNumber = $_POST['offenceNumber'];
            $offenceDetails = $_POST['offenceDetails'];

            // Prepare the SQL statement
            $sql = "INSERT INTO fines (division_id, nominated_person_id, position, date, rego, offence_number, offence_details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
            $statement = mysqli_prepare($this->connect, $sql);

            // Bind parameters and execute the statement
            mysqli_stmt_bind_param($statement, "iisssss", $divisionId, $nominatedPersonId, $position, $date, $rego, $offenceNumber, $offenceDetails);

            if (mysqli_stmt_execute($statement)) {
                echo json_encode(['success' => true]);
            } else {
                // Log errors instead of echoing directly to the user
                error_log('Error in SQL statement: ' . mysqli_stmt_error($statement));
                echo json_encode(['success' => false, 'error' => 'An error occurred while processing your request.']);
            }

            // Close the statement
            mysqli_stmt_close($statement);
        }
    }
}

// Create an instance of FinesController passing the database connection
$finesController = new FinesController($connect);
$finesController->FinesRegister();

// Close the database connection
mysqli_close($connect);
?>
