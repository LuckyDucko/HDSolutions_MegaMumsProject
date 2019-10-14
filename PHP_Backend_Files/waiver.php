<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$stack = array();
$getWaiver = $megaDB->prepare("SELECT CONTENT FROM `CHANGEABLE_CONTENT` WHERE `CONTENT_NAME` = 'Waiver'");
	if($getWaiver->execute())
	{
		$result = $getWaiver->get_result();
		if($result->num_rows)
		{
			while($content = $result->fetch_assoc())
			{
				$waiver = $content;
			}
			array_push($stack, $waiver);
            $getWaiver->close();
		}
		else
		{
			array_push($stack,"0");
		}
	}
	else 
	{
		array_push($stack,"error");
	}
	
echo json_encode($stack);
?>