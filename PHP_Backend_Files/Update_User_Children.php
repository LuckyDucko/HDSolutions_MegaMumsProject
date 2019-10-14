<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$userid = $_POST['userid'];
if($userid == $_SESSION['userid'])
{
	$userRemove = $megaDB->prepare("DELETE FROM USER_CHILDREN WHERE USER_ID = ?");
	$userRemove->bind_param('i',$_SESSION['userid']);
	if(!$userRemove->execute())
	{
		$megaDB->rollback();
		echo json_encode("Error Removing Children");
		$megaDB->commit();
		$megaDB->autocommit(true);
		exit();
	}

	$userChildrenDetails = $megaDB->prepare("INSERT INTO USER_CHILDREN(USER_ID,CHILD_NAME, CHILD_DOB) VALUES(?,?,?)");

	$userChildren = $_POST["children"];
	for($i = 0; $i < count($_POST["children"]); $i=$i+2)
	{
		$name = $userChildren[$i];
		$DOB = $userChildren[$i+1];
		
		$userChildrenDetails->bind_param('iss', $_SESSION['userid'], $name,$DOB);
		if(!$userChildrenDetails->execute())
		{
			$megaDB->rollback();
			echo json_encode("Error Adding Children");
			$megaDB->commit();
			$megaDB->autocommit(true);
			exit();
		}
	}
	echo json_encode("success");
}
else
{
	echo json_encode("not user");
}

$megaDB->commit();
$megaDB->autocommit(true);
?>