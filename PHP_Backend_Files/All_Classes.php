<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

if(isset($_SESSION['userid']))
{
	$getClass = $megaDB->prepare("SELECT * ,1 as OrderMe FROM CLASS_DETAILS WHERE ADDRESS_BRANCH = ? UNION SELECT * ,2 as OrderMe FROM CLASS_DETAILS WHERE ADDRESS_BRANCH != ? ORDER BY OrderMe,CLASS_DATE ASC, STR_TO_DATE(concat('01,01,2018 ',START_TIME), '%d,%m,%Y %h:%i %p')");
	$getClass->bind_param("ss",$_SESSION['branch'],$_SESSION['branch']);
	if($getClass->execute())
	{
		$result = $getClass->get_result();
		while($class = $result->fetch_assoc())
		{
			$classes[] = $class;
		}
		echo json_encode($classes);
	}
	else
	{
		echo json_encode("error");
	}
}
else
{
	$getClass = $megaDB->prepare("SELECT * FROM CLASS_DETAILS ORDER BY CLASS_DATE ASC, STR_TO_DATE(concat('01,01,2018 ',START_TIME), '%d,%m,%Y %h:%i %p') ASC");
	if($getClass->execute())
	{
		$result = $getClass->get_result();
		while($class = $result->fetch_assoc())
		{
			$classes[] = $class;
		}
		echo json_encode($classes);
	}
	else
	{
		echo json_encode("error");
	}
}
$getClass->close();

?>