<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$userid = $_POST['userid'];

if($userid == $_SESSION['userid'])
{
	$updateUserAddress = $megaDB->prepare("UPDATE ADDRESS
		INNER JOIN USER_ADDRESS ON ADDRESS.ADDRESS_ID = USER_ADDRESS.ADDRESS_ID
		SET ADDRESS.ADDRESS_LINE_ONE = ?,
		ADDRESS.ADDRESS_LINE_TWO = ?,
		ADDRESS.ADDRESS_SUBURB = ?,
		ADDRESS.ADDRESS_STATE = ?,
		ADDRESS.ADDRESS_POSTCODE = ?
		WHERE USER_ADDRESS.USER_ID = ?");

    $updateUserAddress->bind_param('sssssi', $_POST['street1'], $_POST['street2'], 
                                    $_POST['suburb'],$_POST['state'],$_POST['postcode'], $userid);
    if(!$updateUserAddress->execute())
    {
        $response = array("response" => "Error Updating User Address");
        echo json_encode($response);
        $megaDB->rollback();
        exit();
    }
	$updateUserAddress->close();
}
else 
{
    $response = array("response" => "Verification Failure");
    echo json_encode($response);
}
$megaDB->commit();
$megaDB->autocommit(true);
$response = array("response" => "Success");
echo json_encode($response);
?>