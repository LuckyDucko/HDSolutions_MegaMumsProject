<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

//we should change this. ask about

$getClass = $megaDB->prepare("SELECT CLASS_DETAILS.* FROM USER_CLASSES INNER JOIN CLASS_DETAILS 
ON USER_CLASSES.CLASS_ID = CLASS_DETAILS.CLASS_ID WHERE USER_CLASSES.USER_ID = ? AND CLASS_DETAILS.USER_ID != ?");
$getClass->bind_param("ii",$_SESSION['userid'], $_SESSION['userid']);

if($getClass->execute())
{
	$result = $getClass->get_result();
	if($result->num_rows < 1)
	{
		$response["none"] = "true";	
		echo json_encode($response);
	}
	else
	{
		while($class = $result->fetch_assoc())
		{
			$classes[] = $class;
		}
		echo json_encode($classes);
		$getClass->close();
	}
}
else 
{
	$response["error"] = "true";	
	echo json_encode($response);
}
?>