<?php
require 'db.php';
$res = $conn->query("SHOW COLUMNS FROM hoadon");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
