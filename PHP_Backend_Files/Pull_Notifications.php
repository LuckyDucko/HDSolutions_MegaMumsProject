<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getNotifications = $megaDB->prepare("SELECT * FROM NOTIFICATIONS WHERE USER_ID = ? ORDER BY DATE_CREATED DESC");
$getNotifications->bind_param('i', $_SESSION['userid']);
if($getNotifications->execute())
{
	$result = $getNotifications->get_result();
	if($result->num_rows)
	{
		while($notification = $result->fetch_assoc())
		{
            $notifications[] = $notification;
        }
        echo json_encode($notifications);
	}
	else
	{
        //nothing
		echo json_encode("No Notifications");
	}
}
else
{
    //error
	echo json_encode("Error");
}

$getNotifications->close();
?>