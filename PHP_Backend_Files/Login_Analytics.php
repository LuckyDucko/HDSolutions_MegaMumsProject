<?php
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

if(strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android')) 
{
   $site = "Mobile";
}
else
{
    $site = "Computer";
}

$analytics = $megaDB->prepare("INSERT INTO LOGIN_ANALYTICS (IP_ADDRESS ,TYPE) VALUES (?,?)");
$analytics->bind_param('ss', $_SERVER['REMOTE_ADDR'],$site);
if(!$analytics->execute())
{
    $response = array("response" => "Error inserting Analytics");
    $megaDB->rollback();
    exit();
}
$analytics->close();
?>
