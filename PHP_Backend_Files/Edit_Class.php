<?php
session_start();
require_once('Distance_Between_Coords.php');
require_once('Change_Address.php');
require_once('Notification_Hub.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$classID = $_POST['classID'];
$classname = $_POST['classname'];
$classdescription = $_POST['classdescription'];
$classcost = $_POST['classcost'];
$childfriendly = $_POST['childfriendly'];
$classdifficulty = $_POST['classdifficulty'];
$classsizetype = $_POST['classsizetype'];
if($classsizetype == "Unlimited")
{
	$classsizemin = '0';
	$classsizemax = '0';
}
else
{
	$classsizemin = $_POST['classsizemin'];
	$classsizemax = $_POST['classsizemax'];
}
$startdate = $_POST['startdate'];
$starttime = $_POST['starttime'];
$classstarttime = date('h:i a',strtotime($starttime));
$endtime = $_POST['endtime'];
$classendtime = date('h:i a',strtotime($endtime));
$equipneeded = $_POST['equipneeded'];
$recurringcheck = $_POST['recurringcheck'];
if($recurringcheck == "Yes")
{
	$recurring = $_POST['recurring'];
}
else
{
	$recurring = "Off";
}
$inputAddress = $_POST['inputAddress'];
$inputAddress2 = $_POST['inputAddress2'];
$inputSuburb = $_POST['inputSuburb'];
$inputState = $_POST['inputState'];
$inputPostcode = $_POST['inputPostcode'];

if(isset($_SESSION['userid']))
{
	if($_COOKIE['userid'] == $_SESSION['userid'] && $_SESSION['privilege'] == "F" || $_SESSION['privilege'] == "BM" || $_SESSION['privilege'] == "A")
	{
		$classUpdated = $megaDB->prepare("INSERT INTO `CLASS_ANALYTICS`(CLASSES_MODIFIED,CLASS_ID) VALUES (?,?)");
		$updated = 1;
		$classUpdated->bind_param('ii',$updated,$classID);
		if(!$classUpdated->execute())
		{
			$response = array("response" => "Error Updating Class Info");
			echo json_encode($response);
			$megaDB->rollback();
			exit();
		}
		else
		{
			$classUpdated->close();
		}
		
		$updateClassDetails = $megaDB->prepare("UPDATE CLASS SET CLASS_NAME=?,CLASS_DESCRIPTION=?,
		CLASS_COST=?,CLASS_DATE=?,CLASS_EQUIPMENT=?, CLASS_RECURRING=?, CLASS_CHILD_FRIENDLY=?,
		CLASS_DIFFICULTY=? WHERE CLASS_ID = ?");
		$updateClassDetails->bind_param('ssssssssi',$classname,$classdescription,$classcost,$startdate, 
		$equipneeded, $recurring,$childfriendly, $classdifficulty, $classID);
		if(!$updateClassDetails->execute())
		{
			$response = array("response" => "Error Updating Class Analytics");
			echo json_encode($response);
			$megaDB->rollback();
			exit();
		}
		else
		{
			$updateClassDetails->close();
		}
		
		$updateClassTime = $megaDB->prepare("UPDATE CLASS_DURATION SET START_TIME=?,END_TIME=? WHERE CLASS_ID = ?");
		$updateClassTime->bind_param('ssi',$classstarttime,$classendtime,$classID);
		if(!$updateClassTime->execute())
		{
			$response = array("response" => "Error Updating Class Time");
			echo json_encode($response);
			$megaDB->rollback();
			exit();
		}
		else
		{
			$updateClassTime->close();
		}
		
		$updateClassSize = $megaDB->prepare("UPDATE CLASS_SIZE SET MINIMUM_SIZE=?,MAXIMUM_SIZE=? WHERE CLASS_ID = ?");
		$updateClassSize->bind_param('ssi',$classsizemin,$classsizemax,$classID);
		if(!$updateClassSize->execute())
		{
			$response = array("response" => "Error Updating Class Size");
			echo json_encode($response);
			$megaDB->rollback();
			exit();
		}
		else
		{
			$updateClassSize->close();
		}
		//need to add something here perhaps?
		//use the functions i have in change address php
		$coords = GetCoords($_POST['inputAddress'], $_POST['inputSuburb'], $_POST['inputState'], $_POST['inputPostcode']);
		$json = json_decode($coords,TRUE);
		$closestName = LocateBranch($json);
		$googleLatLong = ParseCoords($json);

		$updateClassLoc = $megaDB->prepare("UPDATE ADDRESS INNER JOIN CLASS_LOCATION ON ADDRESS.ADDRESS_ID = 
		CLASS_LOCATION.ADDRESS_ID SET ADDRESS_LINE_ONE = ?, ADDRESS_LINE_TWO = ?, ADDRESS_SUBURB = ?, ADDRESS_STATE = ?,
		ADDRESS_POSTCODE = ?, ADDRESS_BRANCH = ?, ADDRESS_COORDINATES = ? WHERE CLASS_LOCATION.CLASS_ID = ?");
		$updateClassLoc->bind_param('ssssissi',$inputAddress,$inputAddress2,$inputSuburb, $inputState, $inputPostcode,$closestName,$googleLatLong, $classID);
		if(!$updateClassLoc->execute())
		{
			$response = array("response" => "Error Updating Class Location");
			echo json_encode($response);
			$megaDB->rollback();
			exit();
		}
		else
		{
			$updateClassLoc->close();
		}
		
		$joinAnalytics = $megaDB->prepare("INSERT INTO JOIN_LEAVE_ANALYTICS (USER_ID, CLASS_ID, ACTION) VALUES (?,?,?)");
		$joined = "Updated Class";
		$joinAnalytics->bind_param('iis', $_SESSION['userid'], $classID, $joined);
		if(!$joinAnalytics->execute())
		{
			$response = array(
				"Error" => "Analytics Fail"
			);
			echo json_encode($response);
			$megaDB->rollback();
			$megaDB->autocommit(true);
			exit();
		}
		$joinAnalytics->close();
	}
	else
	{
		$response = array("response" => "not user");
		echo json_encode($response);
	}
}



$name = $_SESSION['fname'];

$classID = $_POST['classID'];

$BaseMessage = $name . " edited class " . $_POST['classname'];

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




$response = array("response" => "Successfully updated class");
echo json_encode($response);	
$megaDB->commit();
$megaDB->autocommit(true);
?>