<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$response = array();
$getWaiver = $megaDB->prepare("SELECT CONTENT FROM CHANGEABLE_CONTENT WHERE CONTENT_NAME = 'Waiver'");
if($getWaiver->execute())
{

	$getWaiver->store_result();
	$getWaiver->bind_result($waiver);
	$getWaiver->fetch();
	if(!empty($waiver))
	{
		$response["response"] = $waiver;
	}
	else
	{
		$response["response"] = "No Waiver Found, please contact MEGA for the latest MEGA waiver. Sorry for the inconvenience";
	}
}
else 
{
	$response["response"] = "Server Error, No Waiver Found, please contact MEGA for the latest MEGA waiver. Sorry for the inconvenience";
}
$getWaiver->close();
echo json_encode($response);
?>