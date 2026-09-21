<?php
require 'config/db.php';
$tables = ['users', 'accounts', 'transactions', 'admins', 'login_attempts', 'password_reset_requests', 'audit_logs', 'beneficiaries', 'notifications'];

$markdown = "## 11. Real Database Entries (Current State)\n\n";

foreach ($tables as $table) {
    $markdown .= "### `$table` Real Data\n";
    $result = mysqli_query($conn, "SELECT * FROM $table");
    if (!$result) {
        $markdown .= "Error reading table: " . mysqli_error($conn) . "\n\n";
        continue;
    }
    $fields = mysqli_fetch_fields($result);
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    
    if (empty($headers)) {
        $markdown .= "*Table is empty or missing*\n\n";
        continue;
    }
    
    $markdown .= "| " . implode(" | ", $headers) . " |\n";
    $markdown .= "|" . str_repeat("---|", count($headers)) . "\n";
    
    $rowCount = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowCount++;
        $row_data = [];
        foreach ($headers as $header) {
            $val = $row[$header];
            if ($val === null) {
                $val = "NULL";
            } else {
                $val = str_replace("|", "\|", $val);
                $val = str_replace("\n", " ", $val);
                $val = htmlspecialchars($val);
            }
            $row_data[] = $val;
        }
        $markdown .= "| " . implode(" | ", $row_data) . " |\n";
    }
    if ($rowCount == 0) {
        $markdown .= "| *(Empty)* |" . str_repeat(" *(Empty)* |", count($headers) - 1) . "\n";
    }
    $markdown .= "\n";
}

file_put_contents("db_dump.md", $markdown);
echo "Dump completed successfully.";
