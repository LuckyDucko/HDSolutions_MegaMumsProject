<?php
    require_once('DBConnection.php');

    $megaDBConstructor = new MegaDatabaseConnection;
    $megaDB = $megaDBConstructor->connection();

    $usernameCheck = $megaDB->prepare("SELECT USER_USERNAME FROM USER WHERE USER_USERNAME = ? LIMIT 1");
    $usernameCheck->bind_param('s', $_POST["username"]);
    
    $usernameCheck->execute();
    $usernameCheck->bind_result($usernameHold);       
    $usernameCheck->store_result();
    if($usernameCheck->num_rows || $_POST["username"] == '')
    {
        $response = array("response" => "Username Not Unique");
        echo json_encode($response);
    }
    else 
    {
        $response = array("response" => "success");
        echo json_encode($response);
    }
    $usernameCheck->close();
?>