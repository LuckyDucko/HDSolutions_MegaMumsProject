<?php
session_start();
require_once('DBConnection.php');
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
        $response = array("response" => "Error Updating User Info");
        echo json_encode($response);
		$megaDB->rollback();
		exit();
	}
	$updateUserDetails->close();
}
else 
{
    $response = array("response" => "Verification Failure");
    echo json_encode($response);
}
$megaDB->commit();
$megaDB->autocommit(true);
$response = array("response" => "Success");
echo json_encode($response);
?>