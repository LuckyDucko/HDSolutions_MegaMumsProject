<?php
session_start();
require_once('Notification_Hub.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$stack = array();

if(isset($_SESSION['userid']))
{
    if(isset($_POST['message']) && isset($_POST['classID']))
    {
        $facilitator = isset($_POST['facilitator']) ? 1 : 0;
        $parent = isset($_POST['parent']) ? $_POST['parent'] : 0;
        $name = $_SESSION['fname'];
        $addComment = $megaDB->prepare("INSERT INTO COMMENTS(CLASS_ID,USER_ID,MESSAGE,PARENT_COMMENT,FACILITATOR_COMMENT, COMMENT_PHOTO, COMMENTER_NAME) 
                                        VALUES (?,?,?,?,?,?,?)");
        $addComment->bind_param('iisiiss', $_POST['classID'], $_SESSION['userid'], $_POST['message'], $parent, $facilitator, $_SESSION['picture'], $name);
        if(!$addComment->execute())
        {
            $stack["response"] = "Comment Command not executed";
        }
        else
        {

           
            $classID = $_POST['classID'];

            $BaseMessage = $name . " commented on a class you follow";

            $MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

            $DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
            if($DatabaseCheck != "success")
            {
                $stack["response"] = $DatabaseCheck;
            }
            else
            {
                
                $PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $classID);

                if($PushNotificationTagCheck != "success")
                {
                    $stack["response"] = $PushNotificationTagCheck;
                }
                else
                {
                    $AzureCheck = $MegaNotificationHub->SendRemoteNotificationAzure($classID);

                    if($AzureCheck != "success")
                    {
                        $stack["response"] = $AzureCheck;
                    }
                    else
                    {
                        $stack["response"] = "Success";
                    }
                }
            }
            
            
            if(!empty($_POST['className']) && !empty($_POST['classDate']))
			{
                $subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left a comment on your class";
                $message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left a comment on : " . $_POST['className'] . "\n";
                $message .= "Class Date: " . $_POST['classDate'] . "\n";
                $message .= "Comment: " . $_POST['message'] . "\n";
                $message = wordwrap($message,70);
                
                $getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
                INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
                INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
                WHERE CLASS_DETAILS.CLASS_ID = ?");
                $getEmails->bind_param("i",$classID);
                if($getEmails->execute())
                {
                    $result = $getEmails->get_result();
                    if($result->num_rows)
                    {
                        while($users = $result->fetch_assoc())
                        {
                            $notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
                            if(in_array("COMMENTS", $notifications)) 
                            {
                                mail($users['USER_EMAIL'],$subject,$message);
                                $email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
                                $type = "COMMENT_CLASS";
                                $email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
                                $email_log->execute();
                            }
                            else
                            {
                                $stack["email"] = "error";
                            }
                        }
                        $stack["email"] = "sent";
                    }
                    else
                    {
                        $stack["email"] = "none found";
                    }
                }
                else
                {
                    $stack["email"] = "execute error";
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
                        $subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left a comment on your class";
                        $message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left a comment on : " . $name . "\n";
                        $message .= "Class Date: " . $date . "\n";
                        $message .= "Comment: " . $_POST['message'] . "\n";
                        $message = wordwrap($message,70);
                        
                        $getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
                        INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
                        INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
                        WHERE CLASS_DETAILS.CLASS_ID = ?");
                        $getEmails->bind_param("i",$classID);
                        if($getEmails->execute())
                        {
                            $result = $getEmails->get_result();
                            if($result->num_rows)
                            {
                                while($users = $result->fetch_assoc())
                                {
                                    $notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
                                    if(in_array("COMMENTS", $notifications)) 
                                    {
                                        mail($users['USER_EMAIL'],$subject,$message);
                                        $email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
                                        $type = "COMMENT_CLASS";
                                        $email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
                                        $email_log->execute();
                                    }
                                    else
                                    {
                                        $stack["email"] = "error";
                                    }
                                }
                                $stack["email"] = "sent";
                            }
                            else
                            {
                                $stack["email"] = "none found";
                            }
                        }
                        else
                        {
                            $stack["email"] = "execute error";
                        }
                    }
                }
                else
                {
                    $stack["response"] = "Could not find class details";
                }
            }
        }
    }
    else 
    {
        $stack["response"] = "Message or Classid missing";
    }
}
else 
{
    $stack["response"] = "Not Logged in";
}
echo json_encode($stack);
exit();
?>