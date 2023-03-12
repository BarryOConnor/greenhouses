<!DOCTYPE html>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
     <meta charset='UTF-8'>
     <title>ESP8266 Temperature & Humidity DHT11 Sensor</title>
</head>
<body>
<?php

$host = "***REMOVED***";
$user = "***REMOVED***";
$pass = "***REMOVED***";
$db = "***REMOVED***";

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

$result = $pdo->prepare("SELECT * FROM greenhouses ORDER BY taken DESC");
$result->execute();
echo '<table border="0" cellspacing="2" cellpadding="2"> 
      <tr> 
          <td> <font face="Arial">id</font> </td> 
          <td> <font face="Arial">sensor_id</font> </td> 
          <td> <font face="Arial">temperature</font> </td> 
          <td> <font face="Arial">humidity</font> </td> 
          <td> <font face="Arial">taken</font> </td> 
      </tr>';

$conn = null;
?>
</body>
</html>