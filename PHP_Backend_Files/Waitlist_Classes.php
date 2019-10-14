<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getClass = $megaDB->prepare("SELECT CLASS_DETAILS.* FROM CLASS_WAITLIST INNER JOIN CLASS_DETAILS 
ON CLASS_WAITLIST.CLASS_ID = CLASS_DETAILS.CLASS_ID WHERE CLASS_WAITLIST.USER_ID = ? AND CLASS_DETAILS.USER_ID != ?");
$getClass->bind_param("ii",$_SESSION['userid'], $_SESSION['userid']);

if($getClass->execute())
{
	$result = $getClass->get_result();
	if($result->num_rows < 1)
	{
		echo json_encode("none");
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
	echo json_encode("error");
}
?>