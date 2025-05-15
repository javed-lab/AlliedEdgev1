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

class OnboardingController {
    private $connect; // Class-level property to hold the database connection

    // Constructor to set the database connection
    public function __construct($connection) {
        $this->connect = $connection;
    }

    public function OnboardingPanel() {
        // Check if it's an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // Fetch data from onboarding table, ordered by the largest ID first
            $query = "SELECT * FROM onboarding ORDER BY id DESC";
            $result = mysqli_query($this->connect, $query);

            if ($result) {
                $data = array();

                // Fetch data as associative array
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }

                // Close the database connection
                mysqli_close($this->connect);

                // Send data as JSON to the frontend
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data);
                exit;
            } else {
                // Handle query error
                echo json_encode(array("error" => "Error: " . mysqli_error($this->connect)));
                exit;
            }
        } else {
            // Not an AJAX request, don't output JSON directly
            return;
        }
    }
}

// Create an instance of OnboardingController passing the database connection
$onboardingController = new OnboardingController($connect);
$onboardingController->OnboardingPanel();

// Close the database connection
mysqli_close($connect);
?>
