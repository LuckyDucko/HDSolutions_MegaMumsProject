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
	$level = implode(",",$notification);
	$updateAcc = $megaDB->prepare("UPDATE USER_PROFILE SET PROFILE_NOTIFICATION_LEVEL=?, PRIVATE_PROFILE=? WHERE USER_ID=?");
	$updateAcc->bind_param("sii", $level, $private, $userid);
	if(!$updateAcc->execute())
	{
		$response["response"] = "Error Updating User Account";
		echo json_encode($response);
		$megaDB->rollback();
	}
	else
	{
		$response["response"] = "Success";
		echo json_encode($response);
	}
}
$megaDB->commit();
$megaDB->autocommit(true);
?>