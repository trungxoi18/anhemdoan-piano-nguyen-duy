<?php
require 'db.php';
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $table = $row[0];
    echo "TABLE: $table\n";
    $columns = $conn->query("DESCRIBE $table");
    while ($col = $columns->fetch_assoc()) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
?>
