<?php
require 'db.php';
$tables = ['TaiKhoan', 'NhanVien'];
foreach ($tables as $t) {
    echo "=== $t ===\n";
    $r = $conn->query("DESCRIBE $t");
    while($row = $r->fetch_assoc()) {
        echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Null'] . ' | ' . $row['Key'] . ' | ' . $row['Default'] . "\n";
    }
    echo "\n";
}
