<?php
session_start();
$data = $_POST['child'];
parse_str($data, $userChildren);
$_SESSION['userchildren'] = json_encode($userChildren);
$response = array("response" => "success");
echo json_encode($response);
?>




