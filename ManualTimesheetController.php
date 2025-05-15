<?php
$servername = "localhost";  // Change this to your database server's hostname or IP address
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$query = 'SELECT u.id, u.name, m.id, m.rosteredTotalHours, m.clientTotalHours, m.comment, m.dateCreated, m.dateUpdated, m.division, m.timeDiffComment
FROM users AS u
JOIN manual_timesheet AS m
ON u.id = m.staff_id';
$result = mysqli_query($connect, $query);
if ($result) {
    $data = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    header('Content-Type: application/json');

    echo json_encode($data);
} else {
    echo "Query error: " . mysqli_error($connect);
}
// Set the response headers to indicate JSON content
header('Content-Type: application/json');

mysqli_close($connect);
?>
