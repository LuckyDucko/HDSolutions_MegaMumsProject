<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$stack = array();

if(isset($_SESSION['userid']))
{
	$findFac = $megaDB->prepare("SELECT CLASS_FACILITATOR FROM CLASS WHERE CLASS_ID = ?");
	$findFac->bind_param('i', $_POST['classID']);
	if(!$findFac->execute())
	{
		$response = array(
			"Error" => "error finding facilitator",
			"ClassJoined" => "false",
			"FacilitatorStatus" => "false"
		);
		echo json_encode($response);
		exit();
	}
	else
	{
		$findFac->bind_result($facID);       
		$findFac->store_result();
		if($findFac->num_rows)
		{
			$findFac->fetch();
			if($facID == $_SESSION['userid'])
			{
				$stack["FacilitatorStatus"] = "true";
			}	
			else
			{
				$stack["FacilitatorStatus"] = "false";
			}
		}
		else 
		{
			$stack["Error"] = "Statement returned 0 facilitators after execution";
			$stack["FacilitatorStatus"] = "false";
			$stack["ClassJoined"] = "false";
		}
	}

	$findClass = $megaDB->prepare("SELECT * FROM IN_CLASS WHERE USER_ID = ? AND CLASS_ID = ?");
	$findClass->bind_param('ii', $_SESSION['userid'], $_POST['classID']);
	if(!$findClass->execute())
	{
		$stack["Error"] = "Could not execute availability search";
		$stack["ClassJoined"] = "false";
		$stack["FacilitatorStatus"] = "false";
	}
	else
	{
		$findClass->bind_result($USERID,$CLASSID);       
		$findClass->store_result();
		if($findClass->num_rows)
		{
			$stack["Error"] = "false";
			$stack["ClassJoined"] = "true";
		}
		else 
		{
			$stack["Error"] = "false";
			$stack["ClassJoined"] = "false";
		}
	}
	$findFac->close(); 
	$findClass->close();
}
else 
{
	$stack = array(
		"Error" => "Not logged in",
		"ClassJoined" => "fail",
		"FacilitatorStatus" => "fail"
	);
} 
echo json_encode($stack);
?>