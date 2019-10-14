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
else if(!isset($_COOKIE['userid']))
{
	$response = array("response" => "No Cookie Data");
	echo json_encode($response);
	exit();
}
else if($_COOKIE['userid'] != $_SESSION['userid'])
{
	$response = array("response" => "Not User");
	echo json_encode($response);
	exit();
}
$getChildren = $megaDB->prepare("SELECT count(USER_ID) FROM USER_CHILDREN WHERE USER_ID = ?");
$getChildren->bind_param("i",$user);
if($getChildren->execute())
{
    $getChildren->bind_result($count);
    $getChildren->store_result();
    while($getChildren->fetch())
    {

        $removeChild = $megaDB->prepare("DELETE FROM USER_CHILDREN WHERE USER_ID = ? AND CHILD_NAME = ? AND CHILD_DOB= ?");
        $removeChild->bind_param('iss',$user,$childName,$childage);
        if($removeChild->execute())
        {
            $response = array("response" => "success");
            echo json_encode($response);
            $removeChild->close();
        }
        else
        {
            $response = array("response" => "error");
            echo json_encode($response);
            exit();
        }
        
    }
}
else
{
    $response = array("response" => "error");
    echo json_encode($response);
    exit();
}

?>