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
                    case 'test':
                         $this->testClass();
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



     private function testClass(){
          $today = SunsetSunriseInfo::newToday();
          $yesterday = SunsetSunriseInfo::newYesterday();
          $week = SunsetSunriseInfo::newWeek();
          
          echo "TODAY SS yesterday: " . $today->start_sunset . " | SR today: " . $today->sunrise . " | SS Today: ". $today->end_sunset ."\n";
          echo "YESTERDAY SS yesterday: " . $yesterday->start_sunset . " | SR today: " . $yesterday->sunrise . " | SS Today: ". $yesterday->end_sunset ."\n";
          foreach($week->week as $week_day) {
               echo "WEEK SS yesterday: " . $week_day->start_sunset . " | SR today: " . $week_day->sunrise . " | SS Today: ". $week_day->end_sunset ."\n";
          }
     }
}
?>