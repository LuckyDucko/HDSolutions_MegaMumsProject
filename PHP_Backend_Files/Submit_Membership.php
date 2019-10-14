<?php
session_start();
require_once('DBConnection.php');
require_once('Month_Shifter.php');

$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$date = new DateTime(null, new DateTimeZone('Australia/Sydney'));
$preFormatted = $date->format('Y-m-d H:i:s');
$userID = $_POST['userid'];
$active = $_POST['paymentOption'];

$getUser = $megaDB->prepare("SELECT USER_ID, MEMBERSHIP_END FROM MEMBERSHIP WHERE USER_ID = ?");
$getUser->bind_param("i", $userID);
if(!$getUser->execute())
{
    $megaDB->rollback();
    $response = array("response" => "error executing find userid");
    echo json_encode($response);
    exit();
}
else
{
    $result = $getUser->get_result();
    if($result->num_rows)
    {
        $in = true;
    }
    else
    {
        $in = false;
    }
}
$getUser->close();

if($in == true)
{
    //$userID = $_POST['userid'];
    $previousUserMembership = $megaDB->prepare("SELECT MEMBERSHIP_ACTIVE,MEMBERSHIP_END FROM MEMBERSHIP WHERE USER_ID = ?");
    $previousUserMembership->bind_param('i', $userID);
    if(!$previousUserMembership->execute())
    {
        $megaDB->rollback();
        $response = array("response" => "errors on the grabbing prior endDate");
        echo json_encode($response);
        $megaDB->commit();
        $megaDB->autocommit(true);
        exit();
    }
    else
    {
        $previousUserMembership->execute();
        $previousUserMembership->bind_result($MembershipActiveMonthAmount,$MembershipEndDate);  
        $previousUserMembership->fetch();

        $MembershipEndDateDateTime = new DateTime($MembershipEndDate, new DateTimeZone('Australia/Sydney'));

        $GreaterDate = ($MembershipEndDateDateTime > $date) ? $MembershipEndDateDateTime : $date;
        $CombinedActiveTime = ($MembershipEndDateDateTime > $date)? ($active+intval($MembershipActiveMonthAmount)) : $active;

        $endDate = MonthShifter($GreaterDate, intval($_POST['paymentOption']));
        $endDateFormatted = $endDate->format('Y-m-d H:i:s');

        $previousUserMembership->close();

        $userMembership = $megaDB->prepare("UPDATE MEMBERSHIP SET MEMBERSHIP_ACTIVE=?,MEMBERSHIP_START=?,MEMBERSHIP_END=? WHERE USER_ID = ?");

        $userMembership->bind_param('issi',$CombinedActiveTime,$preFormatted,$endDateFormatted,intval($userID));

        if(!$userMembership->execute())
        {
            $megaDB->rollback();
            $response = array("response" => "errors on the endDate");
            echo json_encode($response);
            $megaDB->commit();
            $megaDB->autocommit(true);
            exit();
        }
        else
        {
            $response = array(
                 "userid" => $userID,
                 "paymentOption" => $_POST['paymentOption'],
                 "endDate" => $endDateFormatted,
                 "startDate" => $preFormatted
             );
             echo json_encode($response);
        }

    }


}
else
{
    $userMembership = $megaDB->prepare("INSERT INTO MEMBERSHIP(USER_ID, MEMBERSHIP_ACTIVE, MEMBERSHIP_START, MEMBERSHIP_END) VALUES(?,?,?,?)");

	$endDate = MonthShifter($date, $_POST['paymentOption']);
	$endDateFormatted = $endDate->format('Y-m-d H:i:s');
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
	else
	{
	    $response = array(
            "userid" => $userID,
            "paymentOption" => $_POST['paymentOption'],
            "endDate" => $endDateFormatted,
            "startDate" => $preFormatted
        );
        echo json_encode($response);
	}
}
?>