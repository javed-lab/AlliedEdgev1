<?php

$servername = "localhost";
$username = "tnsmwdztaz";
$password = "vzZ3mFxE2E";
$dbname = "tnsmwdztaz";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to set UTF-8 encoding
function setUTF8Header() {
    header('Content-Type: text/html; charset=utf-8');
}

// Explicitly set UTF-8 encoding
setUTF8Header();

if (isset($_POST['ids'])) {
    $selectedIds = $_POST['ids'];

    if (!is_array($selectedIds)) {
        $selectedIds = array($selectedIds);
    }

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $escapedIds = array_map([$conn, 'real_escape_string'], $selectedIds);

    if (empty($escapedIds)) {
        die("No valid IDs selected for export.");
    }

    $sql = "SELECT
    u.commencement_date,
    u.termination_date,
    u.termination_reason,
    u.id,
    u.name,
    u.middle_name,
    u.surname,
    u.preferred_name,
    u.email,
    u.supplier_id,
    u.phone,
    u.address,
    u.suburb,
    u.state,
    u.postcode,
    u.emergency_contact_full_name,
    u.emergency_contact_relationship,
    u.emergency_contact_mobile,
    u.working_for_provider,
    u.provider_id,
    u.term_condition_accept,
    l.licence_class,
    l.licence_number,
    l.expiry_date,
    l.approved_driver,
    ul.item_name AS user_level_name,
    tm.date_completed, -- Adding completed_date from training_matrix table
    t.title, -- Adding title from training table
    tm.date_completed, -- Adding completed_date from training_matrix table
    lt.item_name AS licence_item_name,
    d.division_name,
    lf.item_name AS lookup_item_name -- Adding item_name from lookup_fields table
FROM users u
LEFT JOIN licences l ON u.id = l.user_id
LEFT JOIN licence_types lt ON l.licence_type_id = lt.id
LEFT JOIN users_user_division_groups udg ON u.id = udg.user_id
LEFT JOIN divisions d ON udg.user_group_id = d.division_id
LEFT JOIN lookup_fields lf ON l.licence_compliance_id = lf.id
LEFT JOIN training_matrix tm ON u.id = tm.staff_id
LEFT JOIN training t ON tm.training_id = t.id
LEFT JOIN user_level ul ON u.user_level_id = ul.id
WHERE u.id IN (" . implode(',', $escapedIds) . ")";
$result = $conn->query($sql);

    if ($result) {
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="complete_employee_list_edge.csv"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');



        // Open a file handle to php://output
        $output = fopen('php://output', 'w');

       // Output the CSV header
       fputcsv($output, array('ID', 'Commencement Date', 'Termination Date', 'Termination Reason', 'User Level', 'Division', 'First Name', 'Middle Name', 'Last Name', 'Preferred Name', 'Email', 'Phone', 'Address', 'Suburb', 'Postcode', 'Emergency Contact Name', 'Emergency Contact Relationship', 'Emergency Contact Mobile', 'Licence Name' ,'Licence Class', 'Licence Number', 'Licence Expiry Date', 'Compliance', 'Working For Provider?', 'Provider ID', 'Terms & Conditions Accepted?', 'Training Name','Completion Date'));

       // Output the data
       while ($row = $result->fetch_assoc()) {
           fputcsv($output, array($row['id'], $row['commencement_date'], $row['termination_date'], $row['termination_reason'], $row['user_level_name'], $row['division_name'], $row['name'], $row['middle_name'], $row['surname'], $row['preferred_name'], $row['email'], $row['phone'], $row['address'], $row['suburb'], $row['postcode'], $row['emergency_contact_full_name'], $row['emergency_contact_relationship'], $row['emergency_contact_mobile'], $row['licence_item_name'] , $row['licence_class'], $row['licence_number'], $row['expiry_date'], $row['lookup_item_name'], $row['working_for_provider'], $row['provider_id'], $row['term_condition_accept'], $row['title'], $row['date_completed'] ));
       }


        // Close the file handle
        fclose($output);

        $conn->close();
        exit;
    } else {
        die("Error: " . $conn->error);
    }
} else {
    die("No IDs selected for export.");
}
?>
