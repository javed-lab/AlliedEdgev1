<?php
$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST["uid"])) {
    $uid = mysqli_real_escape_string($connect, $_POST["uid"]);

    // Prepare and execute a SQL query to fetch 'hoursWorked' for the provided 'site_id'
    $sql = "SELECT hoursWorked FROM totalrosterhours WHERE site_id = '$uid'";
    $result = mysqli_query($connect, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $hoursWorked = $row['hoursWorked'];

            // Create a JSON response
            $response = array('hoursWorked' => $hoursWorked);
            echo json_encode($response);
        } else {
            echo json_encode(array('error' => 'No matching record found for the provided site_id'));
        }
    } else {
        echo json_encode(array('error' => 'Query execution failed: ' . mysqli_error($connect)));
    }
} else {
    echo json_encode(array('error' => 'No uid provided in the POST data'));
}

mysqli_close($connect);
?>
