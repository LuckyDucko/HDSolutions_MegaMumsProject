<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$user= $_POST['userInput'];
$classID = $_POST['classID'];


if(!empty($_POST['userInput']))
{
	$getUser = $megaDB->prepare("SELECT USER_ID FROM USER WHERE USER_USERNAME = ?");
	$getUser->bind_param('s',$user);
	if($getUser->execute())
	{
		$getUser->bind_result($USERID);       
		$getUser->store_result();	
		while($getUser->fetch())
		{
			try
			{
				$addUser = $megaDB->prepare("INSERT INTO USER_CLASSES (USER_ID, CLASS_ID) VALUES (?,?)");
				$addUser->bind_param('ii',$USERID,$classID);
				if($addUser->execute())
				{
					$response = array("response" => "success");
					echo json_encode($response);
				}
				else 
				{
					$response = array("response" => "error");
					echo json_encode($response);
				}
			}
			catch(Exception $e)
			{
				$response = array("response" => $e)
				echo json_encode($response);
			}
		}
	}
} 
else
{
	$response = array("response" => "username not sent/is empty");
	echo json_encode($response);
}
$getUser->close();
$addUser->close();
?>
