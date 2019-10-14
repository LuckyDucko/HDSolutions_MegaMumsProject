<?php
session_start();

$_SESSION["username"] = $_POST["username"];
$_SESSION["email"] = $_POST["email"];
$_SESSION["password"] = password_hash($_POST["password"], PASSWORD_DEFAULT);

$response = array("response" => "success");
echo json_encode($response);
?>
