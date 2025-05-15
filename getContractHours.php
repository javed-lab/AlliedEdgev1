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

    // Prepare and execute a SQL query to fetch all relevant data for the provided 'staff_id'
    $sql = "SELECT clientTotalHours, clientName, dateCreated, dateUpdated, division FROM contractedhours WHERE staff_id = '$uid'";
    $result = mysqli_query($connect, $sql);

    if ($result) {
        $data = array(); // Initialize an array to store the fetched data

        // Fetch all rows and store them in the $data array
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = array(
                'clientTotalHours' => $row['clientTotalHours'],
                'clientName' => $row['clientName'],
                'dateCreated' => $row['dateCreated'],
                'dateUpdated' => $row['dateUpdated'],
                'division' => $row['division']
            );
        }

        // Send the data array as a JSON response
        echo json_encode($data);
    } else {
        echo json_encode(array('error' => 'Query execution failed: ' . mysqli_error($connect)));
    }
} else {
    echo json_encode(array('error' => 'No uid provided in the POST data'));
}

mysqli_close($connect);
?>
