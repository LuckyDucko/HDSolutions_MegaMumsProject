<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$user= $_POST['userid'];
$childName = $_POST['childname'];
$childage = $_POST['childage'];

if(!isset($_SESSION['userid']))
{
	$response = array("response" => "Not Logged In");
	echo json_encode($response);
	exit();
}

$addChild = $megaDB->prepare("INSERT INTO USER_CHILDREN (USER_ID, CHILD_NAME, CHILD_DOB) VALUES (?,?,?)");
$addChild->bind_param('iss',$user,$childName,$childage);
if($addChild->execute())
{
    $response = array("response" => "success");
	echo json_encode($response);
    $addChild->close();
}
else
{
	$response = array("response" => "error");
	echo json_encode($response);
	exit();
}

?>