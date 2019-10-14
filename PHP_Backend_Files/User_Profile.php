<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$userid = $_POST['userid'] == $_SESSION['userid'] ? $_SESSION['userid']: $_POST['userid'];
if(isset($_SESSION['userid']))
{
	$getProfile = $megaDB->prepare("SELECT * FROM USER_PROFILE WHERE USER_ID = ?");
	$getProfile->bind_param("i",$userid);
	if($getProfile->execute())
	{
		$result = $getProfile->get_result();
		$user = $result->fetch_assoc();
		if($user["PRIVATE_PROFILE"] == 1 && $_SESSION['userid'] != $_POST['userid'])
		{
			$user["PROFILE_DESCRIPTION"] = " ";
			$user["USER_ID"] = " ";
			$user["PROFILE_NOTIFICATION_LEVEL"] = " ";
			$user["PROFILE_STATUS"] = " ";
			$user["OWNER"] = "0";
			echo json_encode($user);
		}
		else
		{
			$user["OWNER"] = "1";
			echo json_encode($user);
		}
	}
	else
	{
		echo json_encode("error");
	}
	$getProfile->close();
}
?>