<?php
$db = new PDO('sqlite:D:/project/school/database/database.sqlite');
$stmt = $db->query('SELECT name FROM sqlite_master WHERE type="table"');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
