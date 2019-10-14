<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$userid = $_COOKIE['userid'];
if($userid == $_SESSION['userid'])
{
	$getClass = $megaDB->prepare("SELECT * FROM ALL_CLASSES INNER JOIN USER_CLASSES ON ALL_CLASSES.CLASS_ID = USER_CLASSES.CLASS_ID WHERE USER_CLASSES.USER_ID = ? AND ALL_CLASSES.CLASS_CANCELLED = 1");
	$getClass->bind_param("s", $_SESSION['userid']);

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
}
else
{
	echo json_encode("check fail");
}
?>