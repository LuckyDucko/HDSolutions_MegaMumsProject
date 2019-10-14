<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

if($_POST['username'] == $_SESSION['username'])
{
    $updateAcc = $megaDB->prepare("UPDATE USER SET WAIVER_CHECK=1  WHERE USER_USERNAME=?");
    $updateAcc->bind_param("s", $_POST['username']);
    if(!$updateAcc->execute())
    {
        $response["response"] = "Error Updating User Account";
        echo json_encode($response);
        $megaDB->rollback();
        exit();
    }
    else
    {
        $response["response"] = "Success";
        echo json_encode($response);
    }

    $megaDB->commit();
    $megaDB->autocommit(true);
}
else
{
    $response["response"] = "unauthorised";
    echo json_encode($response);
}

?>