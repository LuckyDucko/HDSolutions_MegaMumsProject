<?php
session_start();
if(!isset($_SESSION['userid']))
{
	echo json_encode("Not Logged in");
}
else
{
	$userData = array("userid" => $_SESSION['userid'], "fname" => $_SESSION['fname'], "lname" => $_SESSION['lname'], "username" => $_SESSION['username'], "privilege" => $_SESSION['privilege'], "profilepic" => $_SESSION["picture"]);
	echo json_encode($userData);
}
?>