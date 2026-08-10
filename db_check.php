<?php
require 'db.php';
$res = $conn->query('SELECT maMau, COUNT(*) as cnt FROM DanSerial WHERE trangThai = "Trong kho" GROUP BY maMau');
echo "Count by maMau:\n";
while($row = $res->fetch_array()) echo $row['maMau'] . ' - ' . $row['cnt'] . "\n";
