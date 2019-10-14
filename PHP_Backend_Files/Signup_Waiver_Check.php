<?php
session_start();

if($_POST['check']==1)
{
    $_SESSION["waiver_check"] = $_POST["check"];
    $response = array("response" => "Waiver checked");
    echo json_encode($response);
}
else 
{
	$response = array("response" => "Waiver must be checked");
	echo json_encode($response);
}
?>


