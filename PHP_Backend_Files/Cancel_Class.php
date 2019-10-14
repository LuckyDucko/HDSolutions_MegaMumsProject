<?php
session_start();
require_once('Notification_Hub.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

if(isset($_SESSION['userid']))
{
	if($_COOKIE['userid'] == $_SESSION['userid'])
	{
		$updateCancelledClass = $megaDB->prepare("UPDATE CLASS SET CLASS_CANCELLED='1', CLASS_CANCEL_REASONING=? WHERE CLASS_ID = ?");
		$updateCancelledClass->bind_param('si',$_POST['reason'], $_POST['classID']);
		if(!$updateCancelledClass->execute())
		{
			$response = array("error" => "Error Updating Class Info");	
			$megaDB->rollback();
		}
		$updateCancelledClass->close();

		$classCancelled = $megaDB->prepare("INSERT INTO `CLASS_ANALYTICS`(CLASSES_CANCELLED,CLASS_ID) VALUES (?,?)");
		$updated = 1;
		$classCancelled->bind_param('ii',$updated,$_POST['classID']);
		if(!$classCancelled->execute())
		{
			$response = array("error" => "Error Inserting Class Analytics");
			$megaDB->rollback();
		}
		else
		{
			$response = array("response" => "success");
			$classCancelled->close();
		}

		$classCancelledUserAnalytics = $megaDB->prepare("INSERT INTO JOIN_LEAVE_ANALYTICS(USER_ID,CLASS_ID,ACTION) VALUES (?,?,?)");
		$updated = 'Cancelled Class';
		$classCancelledUserAnalytics->bind_param('iis',$_SESSION['userid'],$_POST['classID'],$updated);
		if(!$classCancelledUserAnalytics->execute())
		{
			$response = array("error" => "Error Inserting User Analytics");
			$megaDB->rollback();
		}
		else
		{
			$response = array("response" => "success");
			$classCancelledUserAnalytics->close();
		}



		$classID = $_POST['classID'];

        $BaseMessage = "Class ".$_POST['className']." Cancelled";

        $MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

        $DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
        if($DatabaseCheck != "success")
        {
            $response = array("error"=>"failed dbpush");
        }
        else
        {
			$PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $classID);

            if($PushNotificationTagCheck != "success")
            {
                $response = array("error"=>"failed tagspush");
            }
            else
            {
				$AzureCheck = $MegaNotificationHub->SendRemoteNotificationAzure($classID);

                if($AzureCheck != "success")
                {
                    $response = array("error"=>"failed azurepush");
                }
                else
                {
                    $response = array("response" => "success");
                }
            }
        }

		if(!empty($_POST['className']) && !empty($_POST['classDate']))
		{
			$getEmails = $megaDB->prepare("SELECT USER.USER_EMAIL FROM USER 
			INNER JOIN IN_CLASS ON USER.USER_ID = IN_CLASS.USER_ID
			WHERE IN_CLASS.CLASS_ID = ?");
			$getEmails->bind_param('i', $_POST['classID']);
			if($getEmails->execute())
			{
				$subject = "Class ".$_POST['className']." Cancelled";
				$message = "We are sorry to inform you but this class has been cancelled.\n";
				$message .= "\n";
				$message .= "Class Name: ".$_POST['className']."\n";
				$message .= "Class Date: ".$_POST['classDate']."\n";
				$message .= "Reason: ".$_POST['reason']."\n";
				$message = wordwrap($message,70);
				
				$result = $getEmails->get_result();
				if($result->num_rows)
				{
					while($users = $result->fetch_assoc())
					{
						mail($users['USER_EMAIL'],$subject,$message);
						$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
						$type = "CANCEL_CLASS";
						$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
						$email_log->execute();
					}
				}
			}
			else
			{
				$response = array("error"=>"failed email");
			}
		}
		else
		{
			$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
			$getClassDeets->bind_param("i",$classID);
			if($getClassDeets->execute())
			{
				$getClassDeets->bind_result($name,$date);       
				$getClassDeets->store_result();
				while($getClassDeets->fetch())
				{
					$getEmails = $megaDB->prepare("SELECT USER.USER_EMAIL FROM USER 
					INNER JOIN IN_CLASS ON USER.USER_ID = IN_CLASS.USER_ID
					WHERE IN_CLASS.CLASS_ID = ?");
					$getEmails->bind_param('i', $_POST['classID']);
					if($getEmails->execute())
					{
						$subject = "Class ".$name." Cancelled";
						$message = "We are sorry to inform you but this class has been cancelled.\n";
						$message .= "\n";
						$message .= "Class Name: ".$name."\n";
						$message .= "Class Date: ".$date."\n";
						$message .= "Reason: ".$_POST['reason']."\n";
						$message = wordwrap($message,70);
						
						$result = $getEmails->get_result();
						if($result->num_rows)
						{
							while($users = $result->fetch_assoc())
							{
								mail($users['USER_EMAIL'],$subject,$message);
								$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
								$type = "CANCEL_CLASS";
								$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
								$email_log->execute();
							}
						}
					}
					else
					{
						$response = array("error"=>"failed email");
					}
				}
			}
			else
			{
				$response = array("error"=>"failed gathering class details");
			}

		}
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