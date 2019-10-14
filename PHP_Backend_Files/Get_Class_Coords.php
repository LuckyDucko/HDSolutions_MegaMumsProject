<?php
    session_start();
    require_once('DBConnection.php');

    $megaDBConstructor = new MegaDatabaseConnection;
    $megaDB = $megaDBConstructor->connection();
    $classID = $_COOKIE['classID'];
    $getClassCoords = $megaDB->prepare("SELECT ADDRESS_COORDINATES 
                                        FROM ADDRESS INNER JOIN CLASS_LOCATION
                                        ON ADDRESS.ADDRESS_ID = CLASS_LOCATION.ADDRESS_ID 
                                        WHERE CLASS_LOCATION.CLASS_ID = ?");
    $getClassCoords->bind_param('i', $classID);

    if($getClassCoords->execute())
    {
        $getClassCoords->bind_result($classCoords);  
        $getClassCoords->store_result();
        $getClassCoords->fetch();
        $response = array("response" => $classCoords);
        echo json_encode($response);
    }
    else
    {
        $response = array("response" => "Error could not fetch class coords");
        echo json_encode($response);
    }
    $getClassCoords->close();
?>