<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$userid = $_POST['userid'];
$newPass = password_hash($_POST["newPword"], PASSWORD_DEFAULT);

if($userid == $_SESSION['userid'])
{
	$passCheck = $megaDB->prepare("SELECT USER_PASSWORD_HASH FROM USER WHERE USER_ID = ?");
	$passCheck->bind_param('s', $_SESSION['userid']); 
	$passCheck->execute();       
	$passCheck->bind_result($hashedPassword);       
	$passCheck->store_result();

	if($passCheck->num_rows)
	{
		$passCheck->fetch(); 
		if(password_verify($_POST['currentPword'], $hashedPassword))
		{
			$updateUserPass = $megaDB->prepare("UPDATE USER SET USER_PASSWORD_HASH = ? WHERE USER_ID = ?");

			$updateUserPass->bind_param('si', $newPass, $_SESSION['userid']);
			if(!$updateUserPass->execute())
			{
				$response = array("response" => "Error Updating User Password");
        		echo json_encode($response);
        		$megaDB->rollback();
        		exit();
			}
			else
			{
				$response = array("response" => "Success");
        		echo json_encode($response);
			}
			$updateUserPass->close();
		}
		else
		{
			$response = array("response" => "Password Match Error");
			echo json_encode($response);
		}
	}
}
else
{
	$response = array("response" => "Verification Failure");
	echo json_encode($response);
}
	
$megaDB->commit();
$megaDB->autocommit(true);
?>