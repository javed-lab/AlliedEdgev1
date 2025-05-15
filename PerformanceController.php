<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch and sanitize form data
    $actionType = $_POST['actionType'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $date = $_POST['date'] ?? '';
    $recommendedActions = $_POST['recommendedActions'] ?? '';
    $rectifiedActions = $_POST['rectifiedActions'] ?? '';
    $userId = isset($_POST['userId']) ? $_POST['userId'] : null; // Making userId non-mandatory
    $enteredUser = $_POST['enteredUser'] ?? ''; // Retrieve enteredUser from the frontend

    $userName = '';
    $userQuery = "SELECT name, surname FROM users WHERE id = $enteredUser";
    $stmtUser = $conn->prepare($userQuery);
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();

    if ($userResult && $userResult->num_rows > 0) {
        $userRow = $userResult->fetch_assoc();
        // Combine name and surname and store it in the $userName variable
        $userName = $userRow['name'] . ' ' . $userRow['surname'];
    }


    // Handle file upload if a file is present
    $fileUploaded = $_FILES['fileUpload'] ?? null;
    $uploadedFileName = '';

    if ($fileUploaded && $fileUploaded['error'] === UPLOAD_ERR_OK) {
        $uploadDirectory = '/edge/downloads/resources/Performance/Uploads/';
        $fileTmpName = $fileUploaded['tmp_name'];
        $uploadedFileName = $uploadDirectory . basename($fileUploaded['name']);

        // Move the uploaded file to a permanent location
        if (!move_uploaded_file($fileTmpName, $_SERVER['DOCUMENT_ROOT'] . $uploadedFileName)) {
            // Failed to move the file to the destination directory
            error_log('Upload failed: ' . $fileTmpName . ' to ' . $uploadedFileName . ' Error: ' . error_get_last()['message']);
            echo "Failed to move uploaded file to the destination directory";
            exit;
        }
    }

    prd($actionType);

    // Insert data into the database
    $insertQuery = "INSERT INTO performance_reviews (actionType, reason, date, userId, uploadedFileName, enteredUser, rectifiedActions, recommendedActions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssssss", $actionType, $reason, $date, $userId, $uploadedFileName, $enteredUser, $rectifiedActions, $recommendedActions);
    $result = $stmt->execute();
    
    if($result){

        require_once(__DIR__ . '/email_q.php');

        // Instantiate the email object
        $emailObj = new email_q($conn); // Replace $conn with your database connection variable


        $recipients = ['javed.e@alliedmanagement.com.au', 'hr@alliedmanagement.com.au', 'compliance@alliedmanagement.com.au'];
        $subject = "New Performance Disciplinary Action Added";
        $message = "Dear HR and Compliance,<br><br>";
        $message .= "Kindly review the latest development in performance disciplinary action against: <b>$userName</b>, submitted on <b>$date</b>. <br><br>";
        $message .= "Best Regards,<br>Allied EDGE";

        require_once(__DIR__ . '/email_q.php');
        $emailObj = new email_q($conn); // Instantiate the email_q class

        $emailObj->Subject = $subject;
        $emailObj->Body = $message;

        $sendError = false; // Flag to track email sending errors

        foreach ($recipients as $to) {
            $emailObj->clear_all();
            $emailObj->addAddress($to);

            if (!$emailObj->send()) {
                // Set the error flag if email sending fails
                $sendError = true;
                error_log("Email sending error: " . $emailObj->ErrorInfo);
            }
        }

        if ($sendError) {
            $response = [
                'success' => false,
                'message' => 'Form submitted successfully but failed to send emails.',
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Form submitted successfully and emails sent to HR and Compliance.',
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}  


$uidFromURL = $_GET['uid'] ?? ''; // Ensure proper validation/sanitization

// Fetch data for display
$selectQuery = "SELECT * FROM performance_reviews WHERE enteredUser = ?"; // Add a WHERE condition
$stmt = $conn->prepare($selectQuery);
$stmt->bind_param("s", $uidFromURL); // Bind the UID from URL parameter
$stmt->execute();
$result = $stmt->get_result();
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Output the results (for demonstration purposes)
foreach ($data as $row) {
    // Output data as desired, for example:
    echo "Action Type: " . $row['actionType'] . ", Reason: " . $row['reason'] . "<br>";
}

// Close connection
$conn->close();
?>
