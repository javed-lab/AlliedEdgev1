<?php
// Start or resume the session
session_start();

// Check if the user is logged in and session variables are set
if (isset($_SESSION['user_id']) && isset($_SESSION['user_access_level'])) {
    $user_id = $_SESSION['user_id'];
    $user_access_level = $_SESSION['user_access_level'];

    $pages = [
        [
            'id' => 1,
            'title' => 'Manual Timesheet',
            'link' => '/ManualTimesheets',
        ],
        // ... other page data
        [
            'id' => 3,
            'title' => 'Compliance',
            'link' => '#',
            'sub_pages' => [
                [
                    'title' => 'Contractor Details',
                    'link' => '/Contractors',
                ],
                [
                    'title' => 'Fines Register',
                    'link' => '/FinesRegister',
                ],
            ],
        ],
    ];

    if ($user_id >= 554 && $user_access_level >= 600) {
        echo json_encode($pages);
    } else {
        echo json_encode([]); // Empty array if user doesn't meet conditions
    }
} else {
    echo json_encode([]); // Empty array if session variables are not set (user not logged in)
}
?>
