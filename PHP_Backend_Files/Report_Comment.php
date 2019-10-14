<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

if(isset($_SESSION['userid']))
{
	$reportComment = $megaDB->prepare("UPDATE COMMENTS SET COMMENT_REPORTED = 1 WHERE COMMENT_ID = ?");
	$reportComment->bind_param('i',$_POST['commentid']);
	if(!$reportComment->execute())
	{
		$response = array("error" => "Error Removing Comment");	
		$megaDB->rollback();
	}
	$reportComment->close();
	$response = array("success"=>true);
}
else
{
	$response = array("authenticated" => "NO");
}
$megaDB->commit();
$megaDB->autocommit(true);
echo json_encode($response);
?>