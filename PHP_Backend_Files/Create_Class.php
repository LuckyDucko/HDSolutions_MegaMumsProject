<?php
session_start();
//require_once('DBConnection.php');
require_once('Notification_Hub.php');
require_once('Distance_Between_Coords.php');
require_once('Change_Address.php');

$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$stack = array();
if(isset($_SESSION['userid']))
{
	
	if(isset($_POST['facilitator']))
	{
		$findUser = $megaDB->prepare("SELECT USER_ID FROM USER WHERE USER_EMAIL = ? AND USER_PRIVILEGE_LEVEL != ?");
		$level = "U";
		$findUser->bind_param('ss',$_POST['facilitator'], $level);
		if($findUser->execute())
		{
			$findUser->bind_result($uid);
			$findUser->store_result();
			if($findUser->num_rows)
			{
				$findUser->fetch();
				$facilitator = $uid;
			}
			else
			{
				array_push($stack, "none");
				echo json_encode($stack);
				exit();
			}
		}
		else
		{	
			array_push($stack, "Error getting userid");
			echo json_encode($stack);
			exit();
		}
	}
	else
	{
		$facilitator = $_SESSION['userid'];
	}
	
    $classname = $_POST['classname'];
	$classdesc = isset($_POST['classdesc']) ? $_POST['classdesc'] : "DEFAULT CLASS DESCRIPTION";
	$classcost = $_POST['classcost'];
	$childfriendly = $_POST['childfriendly'];
	$difficulty = $_POST['difficulty'];
	$classsizemin = $_POST['classsizemin'];
	$classsizemax = $_POST['classsizemax'];
	$startdate = $_POST['startdate'];
	$starttime = $_POST['starttime'];
	$classstarttime = date('h:i a',strtotime($starttime));
	$endtime = $_POST['endtime'];
	$classendtime = date('h:i a',strtotime($endtime));
	$equipneeded = isset($_POST['equipneeded']) ? $_POST['equipneeded'] : "No Equipment Needed";
	$repeated = $_POST['repeated'];
	$inputAddress = $_POST['inputAddress'];
	$inputAddress2 = $_POST['inputAddress2'];
	$inputSuburb = $_POST['inputSuburb'];
	$inputState = $_POST['inputState'];
	$inputPostcode = $_POST['inputPostcode'];

	//This is to cater to the waitlist right now. add button or field when needed
	$Waitlist = isset($_POST['waitList']) ? $_POST['waitList'] : "1"; //waitlist defaults to true. [in DB]
	//signup open defaults to today. will need a field for that [in DB] (that will submit to the current day)
	//Sponsored defaults to false [in DB] No field for that
	
	
	$type = isset($_POST['type']) ? $_POST['type'] : "Normal"; // kept for error checking. 

	//Stopping here for looking at this one
	$dir = dirname(__FILE__,2);
	if(isset($_FILES['file']['name']))
	{
		if ( $_FILES['file']['error'] > 0 )
		{
			array_push($stack,'Error: ' . $_FILES['file']['error'] . '<br>');
		}
		else 
		{
			if($_FILES['file']['size'] > 500000)
			{
				array_push($stack,"File too Large");
			}
			else
			{				
				$createClass = $megaDB->prepare("INSERT INTO CLASS(CLASS_NAME, CLASS_DESCRIPTION, CLASS_COST, CLASS_TYPE, CLASS_DATE, CLASS_EQUIPMENT, CLASS_RECURRING, 
				CLASS_CHILD_FRIENDLY, CLASS_FACILITATOR, CLASS_DIFFICULTY) VALUES (?,?,?,?,?,?,?,?,?,?)");
				$createClass->bind_param('ssssssssis', $classname, $classdesc, $classcost, $type, $startdate, $equipneeded, $repeated, $childfriendly, $facilitator,$difficulty);
				if(!$createClass->execute())
				{
					array_push($stack, "Error Inserting Class Info");
					$megaDB->rollback();
				}
				else
				{
					$classID = $megaDB->insert_id;
					$createClass->close();
					array_push($stack, "success");
				}
				
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
					array_push($stack,"File Uploaded Successfully");
				}
				else
				{
					array_push($stack,"Unsuccessful File Uploaded");
				}
				
				$insertBannerPic = $megaDB->prepare("UPDATE CLASS SET CLASS_COVER_IMAGE=? WHERE CLASS_ID = ?");
				$insertBannerPic->bind_param('si', $imgLocation, $classID);
				if(!$insertBannerPic->execute())
				{
					array_push($stack, "set banner error");
					$megaDB->rollback();
					exit();
				}
				$insertBannerPic->close();
				
				$insertClassTime = $megaDB->prepare("INSERT INTO CLASS_DURATION (CLASS_ID, START_TIME, END_TIME) VALUES (?,?,?)");
				$insertClassTime->bind_param('iss', $classID, $classstarttime, $classendtime);
				if(!$insertClassTime->execute())
				{
					array_push($stack, "Class time error");
					$megaDB->rollback();
					exit();
				}
				$insertClassTime->close();
				
				$insertClassSize = $megaDB->prepare("INSERT INTO CLASS_SIZE(CLASS_ID, MINIMUM_SIZE, MAXIMUM_SIZE) VALUES (?,?,?)");
				$insertClassSize->bind_param('iii', $classID, $classsizemin, $classsizemax);
				if(!$insertClassSize->execute())
				{
					array_push($stack, "Class size error");
					$megaDB->rollback();
					exit();
				}
				$insertClassSize->close();
				
				$insertUserClass = $megaDB->prepare("INSERT INTO USER_CLASSES(USER_ID,CLASS_ID,CLASS_ATTENDED) VALUES (?,?,?)");
				$join = "1";
				$insertUserClass->bind_param('iii', $_SESSION["userid"], $classID, $join);
				if(!$insertUserClass->execute())
				{
					array_push($stack, "User Classes Join error");
					$megaDB->rollback();
					exit();
				}
				$insertUserClass->close();

				$response = GetCoords($_POST['inputAddress'], $_POST['inputSuburb'], $_POST['inputState'], $_POST['inputPostcode']);
				$json = json_decode($response,TRUE);
				if(isset($_POST['branchInput']))
				{
					$closestName = $_POST['branchInput'];
				}
				else
				{
					$closestName = LocateBranch($json);
				}
				
				$googleLatLong = ParseCoords($json);

				$classAddress = $megaDB->prepare("INSERT INTO ADDRESS(ADDRESS_SUBURB, ADDRESS_BRANCH, ADDRESS_STATE, ADDRESS_POSTCODE, ADDRESS_COORDINATES, ADDRESS_LINE_ONE, ADDRESS_LINE_TWO)
															VALUES(?,?,?,?,?,?,?)");
				$classAddress->bind_param('sssisss', $_POST['inputSuburb'], $closestName, $_POST['inputState'], 
													$_POST['inputPostcode'], $googleLatLong,
													$_POST['inputAddress'], $_POST['inputAddress2']);
				if($classAddress->execute())
				{
					$addressID = $megaDB->insert_id;
				}
				else 
				{
					$megaDB->rollback();
					array_push($stack, "Address didnt not successfully push");
					exit();
				}
				$classAddress->close();


				$addressLink = $megaDB->prepare("INSERT INTO CLASS_LOCATION(CLASS_ID, ADDRESS_ID) VALUES (?,?)");
				$addressLink->bind_param('ii', $classID, $addressID);
				if(!$addressLink->execute())
				{
					$megaDB->rollback();
					array_push($stack,"Issue with link");
					exit();
				} 
				$addressLink->close();

				$classUpdated = $megaDB->prepare("INSERT INTO `CLASS_ANALYTICS`(CLASSES_CREATED,CLASS_ID) VALUES (?,?)");
				$updated = 1;
				$classUpdated->bind_param('ii',$updated,$classID);
				if(!$classUpdated->execute())
				{
					$response = array("response" => "Error Updating Class Analytics");
					echo json_encode($response);
					$megaDB->rollback();
					exit();
				}
				else
				{
					$classUpdated->close();

		            //$megaDB->commit();

			        $BaseMessage = "Class ".$_POST['classname']." Created";

			        $MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

			        $DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
			        if($DatabaseCheck != "success")
			        {
			            array_push($stack, "failed dbpush");
			        }
			        else
			        {
			            $pushtag = 'u' . $classID;
			            $PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $classID);

			            if($PushNotificationTagCheck != "success")
			            {
			                array_push($stack, "failed tagspush");
			            }
			            else
			            {
			                array_push($stack, "success");
			            }
			        }
				}
			}
		}
	}
	else 
	{
		array_push($stack, "no image");
	}
}
echo json_encode($stack);
$megaDB->commit();
$megaDB->autocommit(true);
?>