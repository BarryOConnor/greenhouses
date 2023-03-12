<?php 
     $ch = curl_init();
     $timeout = 30;
     curl_setopt($ch, CURLOPT_URL, '***REMOVED***/api.php?c=cron&a=***REMOVED***');
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
     $data = curl_exec($ch);
     curl_close($ch);
?>