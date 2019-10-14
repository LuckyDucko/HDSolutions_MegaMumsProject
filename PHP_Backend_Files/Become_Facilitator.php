<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$userid = $_POST['userid'];
$message = "User ID: ".$userid."\n";
$message .= "Name: ".$_COOKIE['fname']." ".$_COOKIE['lname']."\n"; 
$message .= "Message: ".$_POST['message'];
$message = wordwrap($message,70);

if($userid == $_SESSION['userid'])
{
	$getBM = $megaDB->prepare("SELECT USER.USER_EMAIL, ADDRESS.ADDRESS_BRANCH FROM `USER` 
	INNER JOIN USER_ADDRESS ON USER.USER_ID = USER_ADDRESS.USER_ID
	INNER JOIN ADDRESS ON USER_ADDRESS.ADDRESS_ID = ADDRESS.ADDRESS_ID
	WHERE USER.USER_PRIVILEGE_LEVEL = ? AND ADDRESS.ADDRESS_BRANCH = ?");
	$bm = "BM";
	$getBM->bind_param('ss',$bm,$_COOKIE['branch']);
	if(!$getBM->execute())
	{
		$response["response"] = "Error Executing Query";
		echo json_encode($response);
	}
	else
	{
		$result = $getBM->get_result();
		if($result->num_rows)
		{
			
			while($manager = $result->fetch_assoc())
			{
				mail($manager['USER_EMAIL'],"Become Facilitator",$message);
				$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
				$type = "BECOME_FACILITATOR";
				$subject = "Request to become facilitator";
				$email_log->bind_param("issss", $_SESSION['userid'],$manager['USER_EMAIL'],$type,$subject,$message);
				$email_log->execute();
			}
			$response["response"] = "Message Sent";
			echo json_encode($response);
		}
		else
		{
			$response["response"] = "Error Finding branch manager";
			echo json_encode($response);
		}
	}
}
?>