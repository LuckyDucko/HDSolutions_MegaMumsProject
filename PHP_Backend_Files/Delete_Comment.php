<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

if(isset($_SESSION['userid']))
{
	if($_SESSION['privilege'] == "A" || $_POST['usernum'] == $_SESSION['userid'])
	{
		$removeComment = $megaDB->prepare("UPDATE COMMENTS SET COMMENT_DELETED = 1 WHERE COMMENT_ID = ?");
		$removeComment->bind_param('i',$_POST['commentid']);
		if(!$removeComment->execute())
		{
			$response = array("error" => "Error Removing Comment");	
			$megaDB->rollback();
		}
		$removeComment->close();
		$response = array("success"=>true);
	}
	else
	{
		$response = array("error"=>"not user");
	}
}
else
{
	$response = array("authenticated" => "NO");
}
$megaDB->commit();
$megaDB->autocommit(true);
echo json_encode($response);
?>