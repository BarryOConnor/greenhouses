<?php class SunsetSunriseInfo {
     public $start_sunset;
     public $sunrise;
     public $end_sunset;
     public $day;

     private static $latitude = YOUR LATITUDE HERE;
     private static $longitude = YOUR LONGITUDE HERE;

     private function __construct() {}

     private static function display($message, $someday){
          echo $message ." = " . " sunrise:" . date("Y-m-d H:i:s", $someday['sunrise']) . " | sunset:" . date("Y-m-d H:i:s", $someday['sunset']) . "\n";
     }

     public static function newWithValues($message, $yesterday, $today) {
          $obj = new self;
          $obj->start_sunset = date("Y-m-d H:i:s",$yesterday['sunset']);
          $obj->sunrise = date("Y-m-d H:i:s",$today['sunrise']);
          $obj->end_sunset = date("Y-m-d H:i:s",$today['sunset']);
          $obj->day = date("Y-m-d", $today['sunrise']);
          return $obj;
     }

     public static function newToday() {
          $yesterday = mktime(1, 0, 0, date("m"), date("d")-1, date("Y"));
          $today = mktime(1, 0, 0, date("m"), date("d"), date("Y"));
          $sun_info_yesterday = date_sun_info($yesterday, self::$latitude, self::$longitude);
          $sun_info_today = date_sun_info($today, self::$latitude, self::$longitude);
          return self::newWithValues("newToday", $sun_info_yesterday, $sun_info_today);
     }

     public static function newYesterday() {
          $yesterday = mktime(1, 0, 0, date("m"), date("d")-2, date("Y"));
          $today = mktime(1, 0, 0, date("m"), date("d")-1, date("Y"));
          $sun_info_yesterday = date_sun_info($yesterday, self::$latitude, self::$longitude);
          $sun_info_today = date_sun_info($today, self::$latitude, self::$longitude);          
          return self::newWithValues("newYesterday", $sun_info_yesterday, $sun_info_today);
     }

     public static function newWeek() {
          $week = array();
          for($i=-1; $i >= -7; $i--) {
               $yesterday = mktime(1, 0, 0, date("m"), date("d")+($i + -1), date("Y"));
               $today = mktime(1, 0, 0, date("m"), date("d")+$i, date("Y"));
               $sun_info_yesterday = date_sun_info($yesterday, self::$latitude, self::$longitude);
               $sun_info_today = date_sun_info($today, self::$latitude, self::$longitude);
               array_push($week, self::newWithValues("newWeek", $sun_info_yesterday, $sun_info_today));
          }
          return $week;
     }
}
?>