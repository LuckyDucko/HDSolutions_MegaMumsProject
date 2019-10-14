<?php
session_start();

if(!empty($_POST["fname"]) && !empty($_POST["lname"]) && !empty($_POST["dob"]) && !empty($_POST["street1"]) && !empty($_POST["suburb"]) && !empty($_POST["state"]) && !empty($_POST["postcode"]) )
{
	$_SESSION['fname'] = $_POST['fname'];
	$_SESSION['lname'] = $_POST['lname'];
	$_SESSION['dob'] = $_POST['dob'];
	$_SESSION['street1'] = $_POST['street1'];
	if(isset($_POST['street2']))
	{
		$_SESSION['street2'] = $_POST['street2'];
	}
	else 
	{
		$_SESSION['street2'] = " ";
	}
	$_SESSION['suburb'] = $_POST['suburb'];
	$_SESSION['state'] = $_POST['state'];
	$_SESSION['postcode'] = $_POST['postcode'];

	$response = array("response" => "success");
	echo json_encode($response);
}
else 
{
	$response = array("response" => "There Was An Issue With Basic Information");
	echo json_encode($response);
}
?>


