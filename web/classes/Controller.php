<?php class Controller {
     private $pdo;
     private $authorised;

     private $parameters = array(
         "Auth" => "a",
         "Command" => "c",
         "Greenhouse" => "g",
         "Temperature" => "t",
         "Humidity" => "h"
     );

     private const MESSAGES = array(
          "OK" => '{"status":"OK"}',
          "OK_WITH_DATA" => '{"status":"OK", %s}',
          "MISSING_PARAMETERS" => '{"status":"ERROR", "message": "Missing Parameters: %s"}',
          "GENERIC_ERROR" => '{"status":"ERROR", "message": "%s"}'
     );



     public function __construct(){
         $this->initialise();           
     }



     public function isAuthorised(){
          return $this->authorised;
     }



     public function routeCommand(){
          if($this->hasValue($this->parameters["Command"])) {
               switch($this->parameters["Command"])
               {
                    case 'save':
                         $this->saveSensorData();
                         break;
                    case 'latest':
                         $this->getLatestData();
                         break;
                    case 'count':
                         $this->getGreenhouseCount();
                         break;
                    case 'data':
                         $this->getGreenhouseData();
                         break;
                    case 'cron':
                         $this->doCronJobActivities();
                         break;
                    default :
                         $this->displayMessageWithData(self::MESSAGES["MISSING_PARAMETERS"], "Command1");
                         break;
               }
          }
     }



     private function initialise(){
          $this->authorised = false;
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
               $this->pdo = new PDO($dsn, $user, $pass, $options);
          } catch (\PDOException $e) {
               throw new \PDOException($e->getMessage(), (int)$e->getCode());
          }


          // check GET parameters exist
          foreach($this->parameters as $key => $value){
               $this->parameters[$key] = empty($_GET[$value]) ? "EMPTY" : htmlspecialchars($_GET[$value]);
          }


          // cant do anything without authorisation so check 
          if($this->checkParamsAreSet("Auth", "Command")) {
               $auth_stmt = $this->pdo->prepare("SELECT COUNT(id) FROM authorised WHERE id = ?");
               $auth_stmt->execute([$this->parameters["Auth"]]);
               $count = $auth_stmt->fetchColumn();
               if ($count > 0) {
                    $this->authorised = true;
                    $this->routeCommand();
               }
          }    
     }



     private function displayMessageWithData($message, $data){
          if(empty($data)){
               return;
          }
          echo sprintf($message, $data);          
     }



     private function checkParamsAreSet(...$params){
          $paramsPresent = true;
          $missingList = "";
          foreach($params as $param){
               if($this->parameters[$param] == "EMPTY") {
                    $paramsPresent = false;
                    $missingList .= $param . ", ";
               }
          }

          $missingList = rtrim($missingList, ", ");
          $this->displayMessageWithData(self::MESSAGES["MISSING_PARAMETERS"], $missingList);
          return $paramsPresent;          
     }


     private function hasValue($param){
          if($param == "EMPTY") {
               return false;
          }
          return true;      
     }


     private function getNumberOfSensors() {
          $stmt = $this->pdo->prepare('SELECT MAX(sensor_id) as num_sensors from greenhouses');
          if ($stmt->execute()) {
               foreach ($stmt as $row) {
                    echo  $row['num_sensors'];
                    return $row['num_sensors'];
               }
          } else {
               echo 0;
               return 0;
          }  
     }
     

     private function saveSensorData() {
          if($this->checkParamsAreSet("Greenhouse", "Temperature", "Humidity")) {
               $stmt = $this->pdo->prepare('INSERT INTO greenhouses (sensor_id, temperature, humidity, taken) VALUES (?, ?, ?, ?)');
               if ($stmt->execute([$this->parameters["Greenhouse"],$this->parameters["Temperature"],$this->parameters["Humidity"], date("Y-m-d H:i:s")])) {
                    echo self::MESSAGES["OK"];
               } else {
                    $this->displayErrorMessage(self::MESSAGES["GENERIC_ERROR"], "Failed to save data!");
               }  
          }
     }



     private function getLatestData() {
          $stmt = $this->pdo->prepare('SELECT sensor_id, temperature, humidity, taken FROM greenhouses gh WHERE taken = (SELECT Max(gho.taken) FROM greenhouses gho WHERE gh.sensor_id = gho.sensor_id) GROUP BY sensor_id;');

          if ($stmt->execute()) {
               $payload = '"greenhouses":[';
               foreach ($stmt as $row) {
                    $payload .= '{"sensor_id":"'.$row['sensor_id'].'", "temperature":"'.$row['temperature'].'", "humidity":"'.$row['humidity'].'", "taken":"'.$row['taken'].'"},';
               }
               $payload = rtrim($payload, ',') . "]";
               $this->displayMessageWithData(self::MESSAGES["OK_WITH_DATA"], $payload);
          } else {
               $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
          }  
     }



     private function getGreenhouseCount() {
          $stmt = $this->pdo->prepare('SELECT sensor_id FROM greenhouses ORDER BY sensor_id DESC LIMIT 1;');
          if ($stmt->execute()) {
               foreach ($stmt as $row) {
                    $payload = '"sensors":"'.$row['sensor_id'].'"';
                    $this->displayMessageWithData(self::MESSAGES["OK_WITH_DATA"], $payload);
               }
          } else {
               $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
          }      
     }



     private function getGreenhouseData() {
          /*if ($this->checkParamsAreSet($this->parameters["Greenhouse"])) {
               $stmt = $this->pdo->prepare('SELECT * FROM greenhouses WHERE sensor_id = ? ORDER BY taken DESC;');
               if ($this->hasValue($this->parameters["Greenhouse"])) {
                    $payload = '"readings": [';
                    foreach ($stmt as $row) {
                         $payload .= '{"temperature":"'.$row['temperature'].'", "humidity":"'.$row['humidity'].'", "taken":"'.$row['taken'].'"},';
                    }
                    $payload = rtrim($payload, ',') ."]";
                    $this->displayMessageWithData(self::OK_WITH_PAYLOAD, $payload);
               } else {
                    $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
               }   
          }   */
     }
     
     private function doCronJobActivities(){
          /*$stmt = $this->pdo->prepare('SELECT * FROM sun_info WHERE day = ?;');
          $stmt->execute([date("Y-m-d")]);
          $count = $stmt->fetchColumn();
          if ($count > 0) {
               $final_stmt = $this->pdo->prepare('UPDATE sun_info SET sunrise = ?, sunset = ? WHERE day = ?;');
          } else {
               $final_stmt = $this->pdo->prepare('INSERT INTO sun_info(sunrise, sunset, day) VALUES (?, ?, ?);');
          }
          $today = SunsetSunriseInfo::newToday();
          if ($final_stmt->execute([$today->sunrise,$today->end_sunset, date("Y-m-d")])) {
               echo self::MESSAGES["OK"];
          } else {
               $this->displayErrorMessage(self::MESSAGES["GENERIC_ERROR"], "Failed to save data!");
          }*/
          $num_sensors = $this->getNumberOfSensors();
          $error_messages = "";

          if($num_sensors > 0) {
               $today = SunsetSunriseInfo::newYesterday();
               for($sensor = 1; $sensor <= $num_sensors; $sensor++) {
                    $cron_stmt = $this->pdo->prepare('SELECT (SELECT AVG(temperature) FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS avg_night, (SELECT AVG(temperature) FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS avg_day, MIN(temperature) AS min_temp, MAX(temperature) AS max_temp, MIN(humidity) AS min_humid, MAX(humidity) AS max_humid FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?;');
                    if($cron_stmt->execute([$sensor, $today->start_sunset, $today->sunrise, $sensor, $today->sunrise, $today->end_sunset, $sensor, $today->start_sunset, $today->end_sunset])){
                         foreach ($cron_stmt as $row) {
                              $count_stmt = $this->pdo->prepare("SELECT COUNT(id) FROM calculated WHERE day = ?");
                              $count_stmt->execute([date("Y-m-d")]);
                              $count = $count_stmt->fetchColumn();
                              if ($count > 0) {
                                   $insert_stmt = $this->pdo->prepare('INSERT INTO calculated(day, sensor_id, avg_night, avg_day, min_temp, max_temp, min_humid, max_humid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                                   if ($insert_stmt->execute([date("Y-m-d"), $sensor, $row['avg_night'], $row['avg_day'], $row['min_temp'], $row['max_temp'], $row['min_humid'], $row['max_humid']])) {
                                        //OK
                                   } else {
                                        $error_messages .= "Failed to save data for cron : sensor id = " . $sensor . "\n\r";
                                   } 
                              }
                         }
                    } else {
                         $error_messages .= "Failed to retrieve data for cron : sensor id = " . $sensor . "\n\r";
                    }
               }
          }
          if ($error_messages != "") {
               mail("***REMOVED***", "Issue With Greenhouse Cron Job", $error_messages);
          }
                    
     }
}
?>