<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$response = array();
$userid = $_POST['userid'];
$private = $_POST['private'];
$notification = $_POST['notification'];

if($userid == $_SESSION['userid'])
{
	$updateAcc = $megaDB->prepare("UPDATE USER_PROFILE SET PROFILE_NOTIFICATION_LEVEL=?, PRIVATE_PROFILE=? WHERE USER_ID=?");
	$updateAcc->bind_param("iii", $notification, $private, $userid);
	if($updateAcc->execute())
	{
		$response["response"] = "success";
		echo json_encode($response);
	}
	else
	{
		$response["response"] = "error";
		echo json_encode($response);
	}
}
$megaDB->commit();
$megaDB->autocommit(true);
?>