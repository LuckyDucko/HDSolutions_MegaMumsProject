<?php
session_start();
require_once('Notification_Hub.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$classID = $_POST['classID'];
$response = array();
if(isset($_SESSION['userid']))
{
	if($_COOKIE['userid'] == $_SESSION['userid'] && $_SESSION['privilege'] == "F" || $_SESSION['privilege'] == "BM" || $_SESSION['privilege'] == "A")
	{
		$dir = dirname(__FILE__,2);
		if(isset($_FILES['file']['name']))
		{
			if ( $_FILES['file']['error'] > 0 )
			{
				$response = array("response" => 'Error: ' . $_FILES['file']['error'] . '<br>');
				echo json_encode($response);
			}
			else 
			{
				if($_FILES['file']['size'] > 500000)
				{
					$response = array("response" => "File too Large");
				}					
				else
				{
					$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
					$newname = "class_".$classID."-banner.".strtolower($ext);
					$imgLocation = "images/Banners/".$newname;
					
					list($width,$height) = getimagesize($_FILES['file']['tmp_name']);
					$newWidth = 800;
					$newHeight = 300;
					$src = imagecreatefromstring(file_get_contents($_FILES['file']['tmp_name']));
					$dst = imagecreatetruecolor($newWidth,$newHeight);
					imagecopyresampled($dst,$src,0,0,0,0,$newWidth,$newHeight,$width,$height);
					imagedestroy($src);
					imagepng($dst,$_FILES['file']['tmp_name']);
					imagedestroy($dst);           
					
					if(move_uploaded_file($_FILES['file']['tmp_name'],  $dir."/Front_End/Register/images/Banners/".$newname))
					{
						$response += ["response" => "Successful file move"];
					}
					else
					{
						$response = array("response" => "File Uploaded Unsuccessfully");
						echo json_encode($response);
					}
					$updateBannerPic = $megaDB->prepare("UPDATE CLASS SET CLASS_COVER_IMAGE=? WHERE CLASS_ID = ?");
					$updateBannerPic->bind_param('si', $imgLocation, $classID);
					if(!$updateBannerPic->execute())
					{
						$response = array("response" => "banner update unsuccessful");
						echo json_encode($response);
						$megaDB->rollback();
						exit();
					}
					else
					{
						$updateBannerPic->close();
						$response["response"] = $response["response"].", Successful db update";

						$name = $_SESSION['fname'];

						$classID = $_POST['classID'];

						$BaseMessage = $name . " edited the class banner for " . $_POST['classname'];

						$MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

						$DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
						if($DatabaseCheck != "success")
						{
							$response = array("response" => $DatabaseCheck);
						}
						else
						{
						    $pushtag = 'u' . $classID;
						    $PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $classID);

						    if($PushNotificationTagCheck != "success")
						    {
						        $response = array("response" => $PushNotificationTagCheck);
						    }
						    else
						    {
						        $AzureCheck = $MegaNotificationHub->SendRemoteNotificationAzure($classID);

						        if($AzureCheck != "success")
						        {
						            $response = array("response" => $AzureCheck);
						        }
						        else
						        {
						            $response = array("response" => "Successfully updated class");
						        }
						    }
						}
					}
				}
			}
		}
		else 
		{
			$response = array("response" => "no image");
		}
	}
	else
	{
		$response = array("response" => "not user");
		echo json_encode($response);
	}
}
echo json_encode($response);
$megaDB->commit();
$megaDB->autocommit(true);	
?>