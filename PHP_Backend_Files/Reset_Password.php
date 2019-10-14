<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getUser = $megaDB->prepare("SELECT USER_EMAIL, GUID FROM RESET_PASSWORD WHERE GUID = ?");
$getUser->bind_param("s",$_POST['hash']);
if($getUser->execute())
{
	$getUser->bind_result($EMAIL,$HASH);
	$getUser->store_result();
	if($getUser->num_rows)
	{
		$getUser->fetch();
		$pass = password_hash($_POST["password"], PASSWORD_DEFAULT);
		$reset = $megaDB->prepare("UPDATE `USER` SET `USER_PASSWORD_HASH`= ?  WHERE USER_EMAIL = ?");
		$reset->bind_param("ss",$pass,$EMAIL);
		if($reset->execute())
		{
			echo json_encode(array("response" => "reset pass"));
		}
		else
		{
			echo json_encode(array("response" => "reset fail"));
			exit();
		}		
		
		$removeUserToken = $megaDB->prepare("DELETE FROM `RESET_PASSWORD` WHERE `USER_EMAIL` = ?");
		$removeUserToken->bind_param("s",$EMAIL);
		if($removeUserToken->execute())
		{
			$removeUserToken->close();
			$getUser->close();
			$reset->close();
		}
		else
		{
			echo json_encode(array("response" => "remove fail"));
			exit();
		}
		
	}
	else
	{
		echo json_encode(array("response" => "none"));
		exit();
	}
}
else
{
	echo json_encode(array("response" => "Select execute fail"));
	exit();
}





?>
