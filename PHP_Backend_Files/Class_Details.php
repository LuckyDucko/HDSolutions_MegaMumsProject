<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getClass = $megaDB->prepare("SELECT * FROM CLASS_DETAILS WHERE CLASS_ID = ?");
$getClass->bind_param("i",$_POST['classID']);
if($getClass->execute())
{
	$result = $getClass->get_result();
	$class[] = $result->fetch_assoc();
	echo json_encode($class);
}
else
{
	echo json_encode("Error fetching class");
}
$getClass->close();
?>