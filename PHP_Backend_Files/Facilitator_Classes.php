<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getClass = $megaDB->prepare("SELECT * FROM CLASS_DETAILS WHERE USER_ID = ?");
$getClass->bind_param("i",$_POST['userid']);

if($getClass->execute())
{
	$result = $getClass->get_result();
	if($result->num_rows)
	{
		while($class = $result->fetch_assoc())
		{
			$classes[] = $class;
		}
		echo json_encode($classes);
	}
	else
	{
		echo json_encode('none');
	}
}
else 
{
	echo json_encode("error");
}
?>