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



     public function __construct($pHost, $pUser, $pPassword, $pDatabase){
         $this->initialise($pHost, $pUser, $pPassword, $pDatabase);           
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



     private function initialise($pHost, $pUser, $pPassword, $pDatabase){
          $this->authorised = false;

          $dsn = "mysql:host=$pHost;dbname=$pDatabase;";
               
          
          $options = [
              PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
              PDO::ATTR_EMULATE_PREPARES   => false,
          ];
          try {
               $this->pdo = new PDO($dsn, $pUser, $pPassword, $options);
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
                    return $row['num_sensors'];
               }
          } else {
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
                    $payload = '"sensors":"'. $row['sensor_id']. '"';
                    $this->displayMessageWithData(self::MESSAGES["OK_WITH_DATA"], $payload);
               }
          } else {
               $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
          }      
     }



     private function getGreenhouseData() {
          if ($this->checkParamsAreSet("Greenhouse")) {
               $today = SunsetSunriseInfo::newToday();
               $this_week = SunsetSunriseInfo::newWeek();
               $payload = '';
               

               $stmt = $this->pdo->prepare('SELECT (SELECT ifNull(ROUND(AVG(temperature),2),\'Not Night Yet!\') FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS today_avg_night, 
                              (SELECT ifNull(ROUND(AVG(temperature),2),\'Not Day Yet!\') FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS today_avg_day, 
                              MIN(temperature) AS today_min_temp, 
                              MAX(temperature) AS today_max_temp, 
                              MIN(humidity) AS today_min_humid, 
                              MAX(humidity) AS today_max_humid 
                              FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?;');
               if($stmt->execute([$this->parameters["Greenhouse"], $today->start_sunset, $today->sunrise,$this->parameters["Greenhouse"], $today->sunrise, $today->end_sunset,$this->parameters["Greenhouse"], $today->start_sunset, $today->end_sunset])){
                    foreach ($stmt as $row) {
                         foreach ($row as $key => $value) {                         
                              $payload .= '"'.$key.'":"'.$value.'",';
                         }
                    }
                    $stmt = $this->pdo->prepare('SELECT b.yesterday_avg_night, b.yesterday_avg_day, b.yesterday_min_temp, b.yesterday_max_temp, b.yesterday_min_humid, b.yesterday_max_humid, ROUND(AVG(avg_night),2 ) as week_avg_night, ROUND(AVG(avg_day),2 ) as week_avg_day, MIN(min_temp) AS week_min_temp, MAX(max_temp) AS week_max_temp, MIN(min_humid) AS week_min_humid, MAX(max_humid) AS week_max_humid FROM calculated JOIN (SELECT c.avg_night as yesterday_avg_night, c.avg_day as yesterday_avg_day, c.min_temp as yesterday_min_temp, c.max_temp as yesterday_max_temp, c.min_humid as yesterday_min_humid, c.max_humid as yesterday_max_humid FROM calculated c WHERE sensor_id = ? AND day = ?) b WHERE sensor_id = ? AND day BETWEEN ? AND ?');
                    if($stmt->execute([$this->parameters["Greenhouse"], $this_week[0]->day, $this->parameters["Greenhouse"], $this_week[6]->day, $this_week[0]->day])){               
                         foreach ($stmt as $row) {
                              foreach ($row as $key => $value) {                         
                                   $payload .= '"'.$key.'":"'.$value.'",';
                              }
                         }
                         $payload = rtrim($payload, ',');
                         $this->displayMessageWithData(self::MESSAGES["OK_WITH_DATA"], $payload);
                    } else {
                         $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
                    }
               } else {
                    $this->displayMessageWithData(self::MESSAGES["GENERIC_ERROR"], "SQL Error!");
               }   
          } 
     }
     
     private function doCronJobActivities(){
          $num_sensors = $this->getNumberOfSensors();
          $error_messages = "";

          if($num_sensors > 0) {
               $this_week = SunsetSunriseInfo::newWeek();
               foreach($this_week as $curr_day) {
                    for($sensor = 1; $sensor <= $num_sensors; $sensor++) {
                         $cron_stmt = $this->pdo->prepare('INSERT IGNORE INTO calculated(day, sensor_id, avg_night, avg_day, min_temp, max_temp, min_humid, max_humid) 
                              SELECT ?, ?, (SELECT ROUND(AVG(temperature),2) FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS avg_night, 
                              (SELECT ROUND(AVG(temperature),2) FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?) AS avg_day, 
                              MIN(temperature) AS min_temp, 
                              MAX(temperature) AS max_temp, 
                              MIN(humidity) AS min_humid, 
                              MAX(humidity) AS max_humid 
                              FROM greenhouses WHERE sensor_id = ? AND taken BETWEEN ? AND ?;');
                         if($cron_stmt->execute([$curr_day->day, $sensor, $sensor, $curr_day->start_sunset, $curr_day->sunrise, $sensor, $curr_day->sunrise, $curr_day->end_sunset, $sensor, $curr_day->start_sunset, $curr_day->end_sunset])){
                              // all is fine
                         } else {
                              $error_messages .= "Failed to save data for cron : sensor id = " . $sensor . "\n\r";
                         }
                    }
               }
          }
          if ($error_messages != "") {
               mail("***REMOVED***", "Issue With Greenhouse Cron Job", $error_messages);
          }                     
     }
}
?>