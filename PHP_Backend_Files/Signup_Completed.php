<?php
session_start();
require_once('DBConnection.php');
require_once('Distance_Between_Coords.php');
require_once('Month_Shifter.php');
require_once('Change_Address.php');

$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$userID = "";
$addressID = "";

$date = new DateTime(null, new DateTimeZone('Australia/Sydney'));

$createAccount = $megaDB->prepare("INSERT INTO USER(USER_USERNAME,USER_PASSWORD_HASH,USER_EMAIL, 
									USER_DOB, USER_LAST_LOGGED, USER_FIRST_NAME, USER_LAST_NAME, 
									USER_PRIVILEGE_LEVEL,WAIVER_CHECK) VALUES(?,?,?,?,?,?,?,?,?)");
/*
	For this date function, we will need to have a ticker to confirm end user area
*/
	
$waivercheck = 1;
$preFormatted = $date->format('Y-m-d H:i:s');
$privLevel = 'U';
$createAccount->bind_param('ssssssssi', $_SESSION['username'], $_SESSION['password'], 
							$_SESSION['email'], $_SESSION['dob'], $preFormatted, $_SESSION['fname'], 
							$_SESSION['lname'], $privLevel,$waivercheck);

if($createAccount->execute())
{
	$userID = $megaDB->insert_id;
} 
else 
{
	$response = array("response" => "Error inserting User into DB");
	echo json_encode($response);
	$megaDB->rollback();
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
}
$createAccount->close();
	
//Insert User Emergency Contact Details
$emergencyContact = (json_decode($_SESSION["primarycontact"]));
$userEmergencyContact = $megaDB->prepare("INSERT INTO USER_EMERGENCY_CONTACT(USER_ID,EMERGENCY_CONTACT_FIRST_NAME, 
											EMERGENCY_CONTACT_LAST_NAME,EMERGENCY_CONTACT_PHONE_NUMBER, 
											EMERGENCY_CONTACT_RELATIONSHIP) VALUES(?,?,?,?,?)");

$userEmergencyContact->bind_param('issss', $userID, $emergencyContact[0], $emergencyContact[1], $emergencyContact[2], $emergencyContact[3]);
if(!$userEmergencyContact->execute())
{
	$megaDB->rollback();
	$response = array("response" => "Error inserting Emergency contact into DB");
	echo json_encode($response);
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
}
$userEmergencyContact->close();

//Insert User Children Details
//ASSUMING DOB IS IN YYYY-MM-DD format 
$userChildren = json_decode($_SESSION["userchildren"], true);
$userChildrenDetails = $megaDB->prepare("INSERT INTO USER_CHILDREN(USER_ID,CHILD_NAME, CHILD_DOB) VALUES(?,?,?)");

for($i = 0; $i < count($userChildren["child"]); $i=$i+2)
{
	$name = $userChildren["child"][$i];
	$DOB = $userChildren["child"][$i+1];
	$userChildrenDetails->bind_param('iss', $userID, $name,$DOB);
	if(!$userChildrenDetails->execute())
	{
		$megaDB->rollback();
		$response = array("response" => "Children Details Error");
		echo json_encode($response);
		$megaDB->commit();
		$megaDB->autocommit(true);
		exit();
	}
}

$userChildrenDetails->close();

$response = GetCoords($_SESSION['street1'],$_SESSION['suburb'],$_SESSION['state'],$_SESSION['postcode']);

$json = json_decode($response,TRUE);
$closestName = LocateBranch($json); // closest branch name

$googleLatLong = ParseCoords($json);

$userAddress = $megaDB->prepare("INSERT INTO ADDRESS(ADDRESS_SUBURB, ADDRESS_BRANCH, ADDRESS_STATE, ADDRESS_POSTCODE, ADDRESS_COORDINATES, ADDRESS_LINE_ONE, ADDRESS_LINE_TWO)
											 VALUES(?,?,?,?,?,?,?)");

$userAddress->bind_param('sssisss', $_SESSION['suburb'], $closestName, $_SESSION['state'], 
									$_SESSION['postcode'], $googleLatLong,
									$_SESSION['street1'], $_SESSION['street2']);
if($userAddress->execute())
{
	$addressID = $megaDB->insert_id;
} 
else 
{
	$megaDB->rollback();
	$response = array("response" => "Address didnt not successfully push");
	echo json_encode($response);
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
}
$userAddress->close();


$addressLink = $megaDB->prepare("INSERT INTO USER_ADDRESS(USER_ID, ADDRESS_ID) VALUES (?,?)");
$addressLink->bind_param('ii', $userID, $addressID);
if(!$addressLink->execute())
{
	$megaDB->rollback();
	$response = array("response" => "Issue With Link");
	echo json_encode($response);
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
} 
$addressLink->close();

$userProfile = $megaDB->prepare("INSERT INTO USER_PROFILE(USER_ID, PROFILE_DESCRIPTION, PROFILE_NOTIFICATION_LEVEL, PRIVATE_PROFILE) VALUES(?,?,?,?)");
$description = "Hello, I'm New here!";
$notification = "ADMIN_ONLY,CLASSES,WAITLIST,COMMENTS";
$private = 0;

$userProfile->bind_param('issi', $userID, $description, $notification, $private);
if(!$userProfile->execute())
{
	$megaDB->rollback();
	$response = array("response" => "Profile Errors");
	echo json_encode($response);
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
}
$userProfile->close();
$megaDB->commit();
$megaDB->autocommit(true);


$userMembership = $megaDB->prepare("INSERT INTO MEMBERSHIP(USER_ID, MEMBERSHIP_ACTIVE, MEMBERSHIP_START, MEMBERSHIP_END) VALUES(?,?,?,?)");

$endDate = MonthShifter($date, 1);
$endDateFormatted = $endDate->format('Y-m-d H:i:s');
$active = 1;
$userMembership->bind_param('iiss', $userID, $active, $preFormatted, $endDateFormatted);
if(!$userMembership->execute())
{
	$megaDB->rollback();
	$response = array("response" => "errors on the endDate");
	echo json_encode($response);
	$megaDB->commit();
	$megaDB->autocommit(true);
	exit();
}


setrawcookie("userid", rawurlencode($userID), strtotime('+30 days'),"/");
setrawcookie("fname", rawurlencode($_SESSION['fname']), strtotime('+30 days'),"/");
setrawcookie("lname", rawurlencode($_SESSION['lname']), strtotime('+30 days'),"/");

$_SESSION["userid"] = $userID;

$message = "Username: ".$_SESSION['username']."\n"; 
$message .= "You Successfully created an account for MEGA Class Booking System.";
$message = wordwrap($message,70);
mail($_SESSION['email'],"New Account Created",$message);

$response = array("response" => "success");
echo json_encode($response);

session_unset();
session_destroy();
?>
