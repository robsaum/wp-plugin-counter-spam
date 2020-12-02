<?php

// MySQL table name = wp_plugin_logger, fields: id,tm,ip,agent
include('wp-config.php');

if (isset($_SERVER)) {
	// Accessing an array should be faster than using the 'getenv' function 
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) { 
        $visip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
        $ip_array = explode(",", $visip); 
        $IPv4 = trim($ip_array[0]); 
    } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) { 
        $IPv4 = $_SERVER["HTTP_CLIENT_IP"]; 
    } else { 
        $IPv4 = $_SERVER["REMOTE_ADDR"]; 
        } 
} else {
    // In case the super global fails	 
    if (getenv('HTTP_X_FORWARDED_FOR')) { 
        $visip = getenv('HTTP_X_FORWARDED_FOR'); 
        $ip_array = explode(",", $visip); 
        $IPv4 = trim($ip_array[0]); 
    } elseif (getenv('HTTP_CLIENT_IP')) { 
        $IPv4 = getenv('HTTP_CLIENT_IP'); 
    } else { 
        $IPv4 = getenv('REMOTE_ADDR'); 
    } 
}  

// From: http://www.plus2net.com/php_tutorial/visitor-logging.php
$decimalEq	= ip2long($IPv4); // convert the octal numbers to their decimal equivalent, to restore the IP use long2ip().
$timestamp	= time();
$browser 	= $_SERVER['HTTP_USER_AGENT'];  // OR @$HTTP_USER_AGENT; To get the user's browser

// Since we are using MySQL we can use the built-in 'inet_aton' function instead of ip2long and 'inet_ntoa' to restore them.
// Connect to the database and write the variables to the table so it will be stored
$connect = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD) or die("Connection to database failed.");
mysql_select_db("wordpress",$connect); 
mysql_query("INSERT INTO wp_plugin_logger (tm,ip,agent) VALUES ('$timestamp',inet_aton('$IPv4'),'$browser')"); 
mysql_close();

echo "Go away bot";

?>