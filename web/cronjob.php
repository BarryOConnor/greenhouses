<?php 
     $ch = curl_init();
     $timeout = 30;
     $hostname = "YOUR HOSTNAME HERE";
     $auth_token = "YOUR AUTH TOKEN";
     curl_setopt($ch, CURLOPT_URL, $hostname . '/api.php?c=cron&a=' . $auth_token);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
     $data = curl_exec($ch);
     curl_close($ch);
?>