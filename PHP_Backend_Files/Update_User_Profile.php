<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$response = array();

$userid = $_POST['userid'];
if($userid == $_SESSION['userid'])
{
	$dir = dirname(__FILE__,2);
    $response["Error"] = "false";
    if(isset($_FILES['file']['name']))
	{
        $response["FileExist"] = "true";
		if ($_FILES['file']['error'] > 0 )
		{
            $response["Error"] = 'Error: ' . $_FILES['file']['error'];
            $response["FileUploadSuccess"] = "false";
            $_SESSION["picture"] = "images/profilePics/user_0-profile-pic.jpg";
		}
		else
		{
			$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
			$newname = "user_".$userid."-profile-pic.".strtolower($ext);
			$imgLocation = "images/profilePics/".$newname;
			
			list($width,$height) = getimagesize($_FILES['file']['tmp_name']);
			$newWidth = 200;
			$newHeight = 200;
			$src = imagecreatefromstring(file_get_contents($_FILES['file']['tmp_name']));
			$dst = imagecreatetruecolor($newWidth,$newHeight);
			imagecopyresampled($dst,$src,0,0,0,0,$newWidth,$newHeight,$width,$height);
			imagedestroy($src);
			imagepng($dst,$_FILES['file']['tmp_name']);
			imagedestroy($dst);           
			
			if(move_uploaded_file($_FILES['file']['tmp_name'],  $dir."/Front_End/Register/images/profilePics/".$newname))
			{
				$response["FileUploadSuccess"] = "true";
				$_SESSION["picture"] = $imgLocation;
			}
			else
			{
                $response["FileUploadSuccess"] = "false";
				$_SESSION["picture"] = "images/profilePics/user_0-profile-pic.jpg";
			}

		}
    }
    else 
    {
        $response["FileExist"] = "false";
        $response["FileUploadSuccess"] = "false";
    }
    $updateUserProfile = $megaDB->prepare("UPDATE USER_PROFILE SET PROFILE_DESCRIPTION=?,PROFILE_PICTURE=? WHERE USER_ID = ?");
    $updateUserProfile->bind_param('ssi', $_POST['about'], $_SESSION["picture"], $userid);
    if(!$updateUserProfile->execute())
    {
        $response = array(
            "Error" => "Error Updating User Info",
            "UpdateSuccess" => "false",
            "FileExist" => "false",
            "FileUploadSuccess" => "false"
        );
        echo json_encode($response);
        $megaDB->autocommit(true);
        exit();
    }
    $updateUserProfile->close();
    $response["UpdateSuccess"] = "true";
}
else 
{
    $response = array(
        "Error" => "Not Logged In",
        "UpdateSuccess" => "false",
        "FileExist" => "false",
        "FileUploadSuccess" => "false"
    );
    echo json_encode($response);
    $megaDB->autocommit(true);
    exit();
}
echo json_encode($response);
$megaDB->commit();
$megaDB->autocommit(true);
?>