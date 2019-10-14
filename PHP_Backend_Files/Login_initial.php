<?php
session_start();
require_once('DBConnection.php');
require_once('Notification_Hub.php');
date_default_timezone_set("Australia/Sydney");

if(!empty($_POST["loginUsername"]) && !empty($_POST["loginPassword"]))
{
    $megaDBConstructor = new MegaDatabaseConnection;
    $megaDB = $megaDBConstructor->connection();
    
    $loginCheck = $megaDB->prepare("SELECT USER_PASSWORD_HASH, USER_DELETED FROM USER WHERE USER_USERNAME = BINARY ?");
    $loginCheck->bind_param('s', $_POST["loginUsername"]); 
    $loginCheck->execute();       
    $loginCheck->bind_result($hashedPassword,$deleteCheck);
    $loginCheck->store_result();

    if($loginCheck->num_rows)
    {
		$loginCheck->fetch();
		
		if($deleteCheck != 1)
		{
			if(password_verify($_POST["loginPassword"], $hashedPassword))
			{
				$dateTime = date("Y-m-d H:i:s");
				$loginTime = $megaDB->prepare("UPDATE USER SET USER_LAST_LOGGED = ? WHERE USER_USERNAME = ?");
				$loginTime->bind_param("ss",$dateTime, $_POST["loginUsername"]);
				$loginTime->execute();

				$loginTime->close();
				
				$userUpdateInformation = $megaDB->prepare("SELECT USER_ID,USER_FIRST_NAME, USER_LAST_NAME, USER_PRIVILEGE_LEVEL FROM USER WHERE USER_USERNAME = ?");
				$userUpdateInformation->bind_param('s', $_POST["loginUsername"]);
				$userUpdateInformation->execute();
				$userUpdateInformation->bind_result($userID,$fName,$lName,$privLevel);
				$userUpdateInformation->store_result();
				$userUpdateInformation->fetch();

				setrawcookie("userid", rawurlencode($userID), strtotime('+30 days'),"/");
				setrawcookie("fname", rawurlencode($fName), strtotime('+30 days'),"/");
				setrawcookie("lname", rawurlencode($lName), strtotime('+30 days'),"/");
				setrawcookie("priv", rawurlencode($privLevel), strtotime('+30 days'),"/");
				$_SESSION["userid"] = $userID;
				$_SESSION["fname"] =  $fName;
				$_SESSION["lname"] = $lName;
				$_SESSION["privilege"] = $privLevel;

				$_SESSION["username"] = $_POST["loginUsername"];
				$userUpdateInformation->close();


				$userProfileInformation = $megaDB->prepare("SELECT PROFILE_NOTIFICATION_LEVEL, PROFILE_PICTURE FROM USER_PROFILE WHERE USER_ID = ?");
				$userProfileInformation->bind_param('i', $userID);
				$userProfileInformation->execute();
				$userProfileInformation->bind_result($notificationLevel, $picture);
				$userProfileInformation->store_result();
				$userProfileInformation->fetch();

				setrawcookie("notifications", rawurlencode($notificationLevel), strtotime('+30 days'),"/");
				setrawcookie("picture", rawurlencode($picture), strtotime('+30 days'),"/");
				$_SESSION["notifications"] = $notificationLevel;
				$_SESSION["picture"] = $picture;


				$userMembershipCheck = $megaDB->prepare("SELECT MEMBERSHIP_ACTIVE FROM MEMBERSHIP WHERE USER_ID = ?");
				$userMembershipCheck->bind_param('i', $userID);
				$userMembershipCheck->execute();
				$userMembershipCheck->bind_result($active);
				$userMembershipCheck->store_result();
				$userMembershipCheck->fetch();

				setrawcookie("membership", rawurlencode($active), strtotime('+30 days'),"/");
				$_SESSION["membership"] = $active;

				$userBranchCheck = $megaDB->prepare("SELECT ADDRESS_BRANCH FROM ADDRESS INNER JOIN USER_ADDRESS ON ADDRESS.ADDRESS_ID = USER_ADDRESS.ADDRESS_ID WHERE USER_ADDRESS.USER_ID = ?");
				$userBranchCheck->bind_param('i', $userID);
				$userBranchCheck->execute();
				$userBranchCheck->bind_result($branchName);
				$userBranchCheck->store_result();
				$userBranchCheck->fetch();

				setrawcookie("branch", rawurlencode($branchName), strtotime('+30 days'),"/");
				$_SESSION["branch"] = $branchName;

				$userProfileInformation->close();
				$userMembershipCheck->close();
				$userBranchCheck->close();


				
				/*
				try
				{
					$pushNotifications = $megaDB->prepare("INSERT INTO NOTIFICATION_TAGS (USER_ID,NOTIFICATION_TAG) VALUES (?,?)");
					$pushNotifications->bind_param('is', $userID, $_SESSION['lname']);
					$pushNotifications->execute();
					$pushNotifications->close();
				}
				catch (Exception $e)
				{
					$response = array("response" => $e);
					echo json_encode($response);
				}
		
				$hub = new NotificationHub("Endpoint=sb://meganotification.servicebus.windows.net/;SharedAccessKeyName=DefaultFullSharedAccessSignature;SharedAccessKey=VFTwjLEvM5ysajLNMaVihBsy3fznciBr5OOn36mbvI0=", "meganotification"); 

				$iOSmessage = '{"aps":{"alert":"Give ' .$_SESSION['fname']. ' ' . $_SESSION['lname'] .  ' a clap! They just logged in!"}}';
				$GCMmessage = '{"data":{"msg":"Give ' .$_SESSION['fname']. ' ' . $_SESSION['lname'] .  ' a clap! They just logged in!"}}';
				try
				{
					$GCMnotification = new Notification("gcm", $GCMmessage);
					$iOSnotification = new Notification("apple", $iOSmessage);
				}
				catch (Exception $e)
				{
					$response = array("response" => $e);
					echo json_encode($response);
				}
				$hub->sendNotification($GCMnotification, $_SESSION['lname']);
				$hub->sendNotification($iOSnotification, $_SESSION['lname']);

				$notificationLevel = "ADMIN_ONLY";
				$StoreNotification = "Logged in! Congratulation! +1 stars mate";

				try
				{
					$userNotifications = $megaDB->prepare("INSERT INTO NOTIFICATIONS (USER_ID, MESSAGE, NOTIFICATION_LEVEL) VALUES (?,?,?)");
					$userNotifications->bind_param('iss', $userID, $StoreNotification, $notificationLevel);
					$userNotifications->execute();
					$userNotifications->close();
				}
				catch (Exception $e)
				{
					$response = array("response" => $e);
					echo json_encode($response);
				}

				*/
				
					include_once('Login_Analytics.php');

					$waiverCheck = $megaDB->prepare("SELECT WAIVER_CHECK FROM USER WHERE USER_USERNAME = ?");
					$waiverCheck->bind_param("s", $_POST["loginUsername"]);
					$waiverCheck->execute();       
					$waiverCheck->bind_result($check);
					$waiverCheck->store_result();
					$waiverCheck->fetch();
					if($check == 0)
					{
						$response = array("response" => "Waiver Not Checked");
						echo json_encode($response);
						$waiverCheck->close();
						exit();
					}
					else
					{
						$waiverCheck->close();

						$response = array("response" => "success");
						echo json_encode($response);
					}
			}
			else
			{
				$response = array("response" => "Something Was Missing In Password/Username");
				echo json_encode($response);
			}
		}
		else
		{
			$response = array("response" => "removed");
			echo json_encode($response);
			exit();
		}
	}
	else
	{
		$response = array("response" => "Something Was Missing In Password/Username");
		echo json_encode($response);
	}
    $loginCheck->close();
}
else
{
    $response = array("response" => "Something Was Missing In Password/Username");
    echo json_encode($response);
}
?>