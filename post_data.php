<?php
date_default_timezone_set('Europe/London');
/*----------

Response Codes:

001 - OK.  Sensor is authorised and data has been saved
010 - FAIL.  Sensor is authorised but data has not been saved
100 - FAIL. Sensor ID is not on the authorised list

---------- */
$auth_id = htmlspecialchars($_GET["a"]);
$sensor_id = htmlspecialchars($_GET["g"]);
$temperature = htmlspecialchars($_GET["t"]);
$humidity = htmlspecialchars($_GET["h"]);

$host = ***REMOVED***;
$user = ***REMOVED***;
$pass = ***REMOVED***;
$db = ***REMOVED***;

$dsn = "mysql:host=$host;dbname=$db;";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$auth_stmt = $pdo->prepare("SELECT COUNT(id) FROM authorised WHERE id = ?");
$auth_stmt->execute([$auth_id]);
$count = $auth_stmt->fetchColumn();

if ($count > 0) {
     $rightnow = date("Y-m-d H:i:s");
     $stmt = $pdo->prepare('INSERT INTO greenhouses (sensor_id, temperature, humidity, taken) VALUES (?, ?, ?, ?)');
     if ($stmt->execute([$sensor_id, $temperature, $humidity, $rightnow])) {
          echo "001";
     } else {
          echo "010";
     }
} else {
     // 
     echo "100";
}
$conn = null;
?>

