<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$userid = $_POST['userid'];
if($userid == $_SESSION['userid'])
{
	$updateEContact = $megaDB->prepare("UPDATE  USER_EMERGENCY_CONTACT SET EMERGENCY_CONTACT_FIRST_NAME=?,EMERGENCY_CONTACT_LAST_NAME=?, 
	EMERGENCY_CONTACT_PHONE_NUMBER=?, EMERGENCY_CONTACT_RELATIONSHIP = ? WHERE USER_ID = ?");
	$updateEContact->bind_param('ssssi', $_POST['efname'], $_POST['elname'], $_POST['epnum'],$_POST['erelationship'], $_SESSION['userid']);
	if(!$updateEContact->execute())
	{
		$response = array("response" => "Error Updating Emergency Contact");
        echo json_encode($response);
        $megaDB->rollback();
        exit();
	}
	$updateEContact->close();
	$response = array("response" => "Success");
	echo json_encode($response);
}
else 
{
	$response = array("response" => "Verification Failure");
	echo json_encode($response);
}
$megaDB->commit();
$megaDB->autocommit(true);
?>