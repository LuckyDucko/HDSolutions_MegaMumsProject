<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
/*
	Need to add a view where it can also get Private_Profile in with this. as to not send data that is 'private'
*/

if(!isset($_SESSION['userid']))
{
	$response = array("response" => "Not Logged In");
	echo json_encode($response);
	exit();
}
else if(!isset($_COOKIE['userid']))
{
	$response = array("response" => "No Cookie Data");
	echo json_encode($response);
	exit();
}
$ID = ($_SESSION['userid'] == $_COOKIE['userid']) ? $_SESSION['userid'] : $_COOKIE['userid'];
$getUser = $megaDB->prepare("SELECT * FROM USER_DETAILS WHERE USER_ID = ?");
$getUser->bind_param("i",$ID);
if($getUser->execute())
{
	$result = $getUser->get_result();
	while($user = $result->fetch_assoc())
	{
		$UserDetails = $user;
	}
	$UserDetails["sessionuserid"] = $_SESSION['userid'];
	echo json_encode($UserDetails);
}
else
{
	$response = array("response" => "error");
	echo json_encode($response);
	exit();
}
?>