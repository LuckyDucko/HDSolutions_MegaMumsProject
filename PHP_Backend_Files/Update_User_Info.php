<?php
session_start();
require_once('DBConnection.php');
require_once('Distance_Between_Coords.php');
require_once('Change_Address.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$userid = $_POST['userid'];

if($userid == $_SESSION['userid'])
{
	$updateUserDetails = $megaDB->prepare("UPDATE USER SET USER_EMAIL=?,USER_FIRST_NAME=?, USER_LAST_NAME=? WHERE USER_ID = ?");

	$updateUserDetails->bind_param('sssi', $_POST['email'], $_POST['fname'], $_POST['lname'], $_SESSION['userid']);
	if(!$updateUserDetails->execute())
	{
		echo json_encode("Error Updating User Info");
		$megaDB->rollback();
		exit();
	}
	$updateUserDetails->close();
//update check here aswell? for the coords and branch stuff? or perhaps we make 
//them select their own branch?
//use the functions i have in change address php
	$updateUserAddress = $megaDB->prepare("UPDATE ADDRESS
		INNER JOIN USER_ADDRESS ON ADDRESS.ADDRESS_ID = USER_ADDRESS.ADDRESS_ID
		SET ADDRESS.ADDRESS_LINE_ONE = ?,
		ADDRESS.ADDRESS_LINE_TWO = ?,
		ADDRESS.ADDRESS_BRANCH = ?,
		ADDRESS.ADDRESS_SUBURB = ?,
		ADDRESS.ADDRESS_STATE = ?,
		ADDRESS.ADDRESS_POSTCODE = ?
		WHERE USER_ADDRESS.USER_ID = ?");
		
	$coords = GetCoords($_POST['street1'], $_POST['suburb'], $_POST['state'], $_POST['postcode']);
	$json = json_decode($coords,TRUE);
	$closestName = LocateBranch($json);

	$updateUserAddress->bind_param('ssssssi', $_POST['street1'], $_POST['street2'], $closestName,
		$_POST['suburb'],$_POST['state'],$_POST['postcode'], $userid);
		if(!$updateUserAddress->execute())
		{
			echo json_encode("Error Updating User Address");
			$megaDB->rollback();
			exit();
		}
	$updateUserAddress->close();
	
	$_SESSION['fname'] = $_POST['fname'];
	$_SESSION['lname'] = $_POST['lname'];
}
$megaDB->commit();
$megaDB->autocommit(true);
echo json_encode("success");
?>