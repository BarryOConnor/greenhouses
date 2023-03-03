<?php 
$today = mktime(1, 0, 0, date("m"), date("d"), date("Y"));
$latitude = ***REMOVED***;
$longitude = ***REMOVED***;
$sun_info_today = date_sun_info($today, $latitude, $longitude);
$sunset = date("Y-m-d H:i:s",$sun_info_today['sunset']);
$now = date("Y-m-d H:i:s");
if($now > $sunset) {
     $ch = curl_init();
     $timeout = 30;
     curl_setopt($ch, CURLOPT_URL, 'http://***REMOVED***/api.php?c=cron&a=***REMOVED***');
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
     $data = curl_exec($ch);
     curl_close($ch);
}
?>