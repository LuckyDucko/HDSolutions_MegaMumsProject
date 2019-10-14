<?php
session_start();

$primaryContact = array($_POST["efname"], $_POST["elname"], $_POST["epnum"], $_POST["relationship"]);
$_SESSION["primarycontact"] = json_encode($primaryContact);
$response = array("response" => "success");
echo json_encode($response);

/*
    Assuming javascript will determine that if one is filled in secondary contact
    they all should be, so we only check one.
*/

/*
    if(isset($_POST["SECONDARYCONTACTFNAME"]))
    {
        $primaryContact = array($_POST["SECONDARYCONTACTFNAME"], $_POST["SECONDARYCONTACTLNAME"], $_POST["SECONDARYCONTACTPHONE"]);
        $_SESSION["SECONDARYCONTACT"] = serialize($SecondaryContact);
    }
*/


/*
    And Finished
*/
?>

