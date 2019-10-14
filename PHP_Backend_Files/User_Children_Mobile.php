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
$getChildren = $megaDB->prepare("SELECT CHILD_NAME, CHILD_DOB FROM USER_CHILDREN WHERE USER_ID = ?");
$getChildren->bind_param("i",$ID);
if($getChildren->execute())
{
	$result = $getChildren->get_result();
	while($child = $result->fetch_assoc())
	{
		$children[] = $child;
	}

	echo json_encode($children);
    $getChildren->close();
}
else
{
	$response = array("response" => "error");
	echo json_encode($response);
	exit();
}
?>