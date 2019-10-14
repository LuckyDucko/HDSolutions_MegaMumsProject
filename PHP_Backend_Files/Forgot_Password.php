<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$sendUser = $megaDB->prepare("INSERT INTO `RESET_PASSWORD`(`USER_EMAIL`, `GUID`) VALUES (?,SHA2(FLOOR(RAND()*2000000000),0))");
$sendUser->bind_param("s",$_POST['userEmail']);
if(!$sendUser->execute())
{
	echo json_encode(array("response" => "fail"));
	exit();
}
$sendUser->close();

$getUser = $megaDB->prepare("SELECT DISTINCT USER_EMAIL, GUID FROM RESET_PASSWORD WHERE USER_EMAIL = ? LIMIT 1");
$getUser->bind_param("s",$_POST['userEmail']);
if($getUser->execute())
{
	$getUser->bind_result($EMAIL,$HASH);
	$getUser->store_result();
	if($getUser->num_rows)
	{
		$getUser->fetch();
		$subject = "MEGA Reset Password";
		$message = "Click this URL to reset password: http://frank.theworkpc.com/megafiles/website/Front_End/Register/resetPassword.html?hash={$HASH}";	
		mail($EMAIL,$subject,$message);	
		echo json_encode(array("response" => "sent"));
	}
	else
	{
		echo json_encode(array("response" => "none"));
		exit();
	}
}
else
{
	echo json_encode(array("response" => "get fail"));
	exit();
}



?>
