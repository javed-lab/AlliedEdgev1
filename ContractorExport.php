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
    u.id,
    u.name,
    u.surname,
    u.email,
    u.supplier_id,
    u.phone,
    u.abn,
    u.address,
    u.suburb,
    u.postcode,
    u.manager_incharge_name,
    u.manager_incharge_mobile,
    u.manager_incharge_email,
    u.manager_incharge2_name,
    u.manager_incharge2_mobile,
    u.manager_incharge2_email,
    l.licence_class,
    l.licence_number,
    l.expiry_date,
    lt.item_name AS licence_item_name,
    d.division_name,
    lf.item_name AS lookup_item_name -- Adding item_name from lookup_fields table
FROM users u
LEFT JOIN licences l ON u.id = l.user_id
LEFT JOIN licence_types lt ON l.licence_type_id = lt.id
LEFT JOIN users_user_division_groups udg ON u.id = udg.user_id
LEFT JOIN divisions d ON udg.user_group_id = d.division_id
LEFT JOIN lookup_fields lf ON l.licence_compliance_id = lf.id
WHERE u.id IN (" . implode(',', $escapedIds) . ")";
$result = $conn->query($sql);

    if ($result) {
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="complete_contractors_list_edge.csv"');
        header('Cache-Control: max-age=0');

        // Open a file handle to php://output
        $output = fopen('php://output', 'w');

       // Output the CSV header
       fputcsv($output, array('Supplier ID', 'Division', 'First Name', 'Last Name', 'Email', 'Phone', 'ABN', 'Address', 'Suburb', 'Postcode', 'Manager In Charge Name', 'Manager In Charge Mobile', 'Manager In Charge Email', '2nd Manager In Charge Name', '2nd Manager In Charge Mobile', '2nd Manager In Charge Email', 'Licence Name' ,'Licence Class', 'Licence Number', 'Licence Expiry Date', 'Compliance'));

       // Output the data
       while ($row = $result->fetch_assoc()) {
           fputcsv($output, array($row['supplier_id'], $row['division_name'], $row['name'], $row['surname'], $row['email'], $row['phone'], $row['abn'], $row['address'], $row['suburb'], $row['postcode'], $row['manager_incharge_name'], $row['manager_incharge_mobile'], $row['manager_incharge_email'], $row['manager_incharge2_name'], $row['manager_incharge2_mobile'], $row['manager_incharge2_email'], $row['licence_item_name'] , $row['licence_class'], $row['licence_number'], $row['expiry_date'], $row['lookup_item_name'] ));
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
