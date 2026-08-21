<?php
require 'db.php';

echo "=== MAUDAN COLUMNS ===\n";
$res = $conn->query('SHOW COLUMNS FROM MauDan');
while($row = $res->fetch_assoc()) echo $row['Field'] . ' - ' . $row['Type'] . "\n";

echo "\n=== DANSERIAL COLUMNS ===\n";
$res = $conn->query('SHOW COLUMNS FROM DanSerial');
while($row = $res->fetch_assoc()) echo $row['Field'] . ' - ' . $row['Type'] . "\n";
