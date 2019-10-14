<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

if(isset($_SESSION['userid']))
{
	if($_COOKIE['userid'] == $_SESSION['userid'] && $_SESSION['privilege'] == "F" || $_SESSION['privilege'] == "BM" || $_SESSION['privilege'] == "A")
	{
		$getRoll = $megaDB->prepare("SELECT CLASS.CLASS_NAME, USER.USER_ID, USER.USER_FIRST_NAME, USER.USER_LAST_NAME, EMERGENCY_CONTACT_PHONE_NUMBER,
									EMERGENCY_CONTACT_FIRST_NAME, CLASS_ATTENDED
									FROM USER_CLASSES
									INNER JOIN USER ON USER_CLASSES.USER_ID = USER.USER_ID
									INNER JOIN USER_EMERGENCY_CONTACT ON USER_CLASSES.USER_ID = USER_EMERGENCY_CONTACT.USER_ID
									INNER JOIN CLASS ON USER_CLASSES.CLASS_ID = CLASS.CLASS_ID
									WHERE USER_CLASSES.CLASS_ID = ?");

		$getRoll->bind_param("i",$_POST['classID']);
		
		if($getRoll->execute())
		{
			$result = $getRoll->get_result();
			if($result->num_rows) // if it is 0, then it will be false, everything else is true.
			{
				while($roll = $result->fetch_assoc())
				{
					$attending[] = $roll;
				}
				echo json_encode($attending);
			}
			else
			{

				$response = array("Error" => "No Waitlist");
				echo json_encode($response);
			}
		}
		else 
		{
			$response = array("Error" => "Error On Getting The Roll");
			echo json_encode($response);
		}
		$getRoll->close();
	}
	else
	{
		$response = array("Error" => "Not Facilitator");
		echo json_encode($response);
	}
}
else
{
	$response = array("Error" => "Not Logged In");
	echo json_encode($response);
}
?>