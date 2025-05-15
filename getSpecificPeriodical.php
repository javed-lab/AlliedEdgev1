<?php

$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";
error_reporting(E_ALL);
ini_set('display_errors', 1);

$connect = mysqli_connect($servername, $username, $password, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

$statusColors = [
    10 => '#E64A00',   // Status ID 10 (Pending) color
    20 => '#0000A0',   // Status ID 20 (In Progress) color
    30 => '#444444',   // Status ID 30 (Stuck) color
    40 => '#008C00',   // Status ID 40 (Completed) color
    50 => '#8C0000',   // Status ID 50 (Cancelled) color
    // Add more status_id to color mappings as needed
];

$site_id = isset($_GET['site_id']) ? $_GET['site_id'] : null;

// Check if a site_id was selected
if ($site_id) {
    // Fetch and display the periodical details for the selected site_id
    // You can query your database to retrieve the data for the specific site_id

    $sql = "SELECT tasks.id, tasks.description, task_schedules.date, task_status.id
    FROM tasks
    LEFT JOIN task_schedules ON tasks.id = task_schedules.task_id
    LEFT JOIN task_status ON task_schedules.status_id = task_status.id
    WHERE tasks.site_id = $site_id";

$result = mysqli_query($connect, $sql);

if ($result) {
    echo ' <style>
    table {
        border-collapse: collapse;
        width: 100%;
        color: #333;
        font-family: Arial, sans-serif;
        font-size: 14px;
        text-align: left;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        margin: auto;
        margin-top: 60px;
        margin-bottom: 50px;
        object-position: center;
    }
    
    table th {
        background-color: #233251;
        color: #fff;
        font-weight: bold;
        padding: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-top: 1px solid #fff;
        border-bottom: 1px solid #ccc; 
    }

    table tr:nth-child(even) td {
        background-color: #f2f2f2;
    }

    table tr:hover td {
        background-color: #CDEEFD;
    }

    table td {
        background-color: #fff;
        padding: 10px;
        border-bottom: 1px solid #ccc;
        font-weight: bold;
        width:50%;
    }
    
    .date-column {
        width: 30px; // Adjust the width of the date column as needed
        padding: 5px;
        text-align: center;
    }

    </style>';

// ...
echo '<table class="totals">';
echo '<tr>';
echo '<th>Tasks List</th>';
echo '<th id="month">Jan</th>';
echo '<th id="month">Feb</th>';
echo '<th id="month">Mar</th>';
echo '<th id="month">Apr</th>';
echo '<th id="month">May</th>';
echo '<th id="month">Jun</th>';
echo '<th id="month">Jul</th>';
echo '<th id="month">Aug</th>';
echo '<th id="month">Sep</th>';
echo '<th id="month">Oct</th>';
echo '<th id="month">Nov</th>';
echo '<th id="month">Dec</th>';
echo '</tr>';
$rowClass = 'c1';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr class="' . $rowClass . '">';
    echo '<td class="pt">' . $row['description'] . '</td>';
    // Generate the date columns and set the background color based on status_id
    $monthColumns = range(1, 12); // Assuming 12 months
    foreach ($monthColumns as $month) {
        $date = $row['date'];
        $monthClass = date('n', strtotime($date)) == $month ? ' date-column' : '';
        $statusColor = isset($statusColors[$row['id']]) ? $statusColors[$row['id']] : 'default-color'; // Get the color based on status_id
        echo '<td class="date-column' . $monthClass . '">';
        
        // Display the numbers inline with dividers
        if ($month == 1) {
            $numbers = ['02', '09', '16', '23']; //January days
        } elseif ($month == 2) {
            $numbers = ['06', '13', '20', '27']; // February days
        } elseif ($month ==3){
            $numbers = ['06','13','20','27']; //  March days
        } elseif ($month == 4) {
            $numbers = ['03', '10', '17', '24']; // April days
        } elseif ($month == 5) {
            $numbers = ['08', '15', '22', '29']; // May days
        }  elseif ($month == 6) {
            $numbers = ['05', '12', '19', '26']; // June days
        }  elseif ($month == 7) {
            $numbers = ['03', '10', '17', '24']; // July days
        }  elseif ($month == 8) {
            $numbers = ['07', '14', '21', '28']; // August days
        }  elseif ($month == 9) {
            $numbers = ['04', '11', '18', '25']; // September days
        }  elseif ($month == 10) {
            $numbers = ['02', '09', '16', '23']; // October days
        }  elseif ($month == 11) {
            $numbers = ['06', '13', '20', '27']; // November days
        } elseif ($month == 12) {
            $numbers = ['04', '11', '18', '25']; // December days
        } 
        foreach ($numbers as $number) {
            // Get the day value from the date
            $dayValue = date('d', strtotime($row['date']));
            
            // Check if the current number matches the day value
            if ($number == $dayValue) {
                // Check if the status_id exists in $statusColors
                if (isset($statusColors[$row['id']])) {
                    $color = $statusColors[$row['id']];
                } else {
                    // Use a default color if status_id doesn't exist in $statusColors
                    $color = 'default-color';
                }
            } else {
                // Use a default color if the number doesn't match the day value
                $color = 'default-color';
            }
        
            // Create a div with a specified background color
            echo '<div id="num' . $number . '" style="display: inline; border: 1px solid #000; padding: 2px; margin: 2px; background-color: ' . $color . ';">' . $number . '</div>';
        }
        
        
        echo '</td>';
    }
    echo '</tr>';

    // Alternate row class
    $rowClass = ($rowClass == 'c1') ? 'c2' : 'c1';
}

echo '</table>';

} else {
    echo 'Error fetching data: ' . mysqli_error($connect);
}


mysqli_close($connect);

} else {
echo 'Please select a site_id to view specific periodical details.';
}


?>
