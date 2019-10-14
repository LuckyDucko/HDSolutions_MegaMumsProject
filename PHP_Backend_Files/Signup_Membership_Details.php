<?php
session_start();
$_SESSION["membership"] = $_POST["membership"];
$response = array("response" => "success");
echo json_encode($response);
?>
