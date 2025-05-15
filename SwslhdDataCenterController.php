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

class SwslhdDataCenterController {
    private $connect; // Class-level property to hold the database connection

    // Constructor to set the database connection
    public function __construct($connection) {
        $this->connect = $connection;
    }

    public function dataCentre() {
    }
}

// Create an instance of FinesController passing the database connection
$SwslhdDataCenterController = new SwslhdDataCenterController($connect);
$SwslhdDataCenterController->dataCentre();

// Close the database connection
mysqli_close($connect);
?>
