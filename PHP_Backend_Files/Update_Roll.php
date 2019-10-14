<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
if($_COOKIE['userid'] == $_SESSION['userid'] && $_SESSION['privilege'] == "F" || $_SESSION['privilege'] == "BM" || $_SESSION['privilege'] == "A")
{
	$attendance = $_POST['attendance'];
	$classID = $_POST['classID'];
	foreach($attendance as $attend)
	{
		$updateRoll = $megaDB->prepare("UPDATE USER_CLASSES SET CLASS_ATTENDED=? WHERE USER_ID=? and CLASS_ID=?");
		$updateRoll->bind_param("iii",$attend['value'], $attend['userid'], $classID);
		$updateRoll->execute();
	}
	
	$response = array("response" => "success");
	echo json_encode($response);
	$updateRoll->close();
}
else
{
	$response = array("response" => "not user");
	echo json_encode($response);
}
?>