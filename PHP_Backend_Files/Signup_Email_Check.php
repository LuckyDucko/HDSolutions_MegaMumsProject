<?php
    require_once('DBConnection.php');

    $megaDBConstructor = new MegaDatabaseConnection;
    $megaDB = $megaDBConstructor->connection();
    
    $emailCheck = $megaDB->prepare("SELECT USER_EMAIL FROM USER WHERE USER_EMAIL = ? LIMIT 1");
    $emailCheck->bind_param('s', $_POST["email"]); 

    $emailCheck->execute(); 
    $emailCheck->bind_result($emailHold);  
    $emailCheck->store_result();   

    if($emailCheck->num_rows || $_POST["email"] == '')
    {
        $response = array("response" => "Email Not Unique");
        echo json_encode($response);
    }
    else 
    {
        $response = array("response" => "success");
        echo json_encode($response);
    }
    $emailCheck->close();
?>