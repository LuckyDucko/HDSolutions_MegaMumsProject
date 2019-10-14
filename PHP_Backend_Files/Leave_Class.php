<?php
session_start();
require_once('DBConnection.php');
//require_once('Notification_Hub.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$stack = array();
if(!isset($_POST['classID']))
{
	$response = array(
		"ErrorA" => "Did not recieve classID",
		"ErrorB" => "false",
		"LeaveSuccessful" => "false",
		"WaitlistUpdateSuccessful" => "false"
	);
	echo json_encode($response);
	$megaDB->autocommit(true);
	exit();
}
else if(isset($_SESSION['userid']))
{ 
	$leaveClass = $megaDB->prepare("DELETE FROM USER_CLASSES WHERE USER_ID = ? AND CLASS_ID = ?");
	$leaveClass->bind_param('ii', $_SESSION['userid'], $_POST['classID']);
	if(!$leaveClass->execute())
	{
		$response = array(
			"ErrorA" => "Delete from User Classes unsuccessful",
			"ErrorB" => "false",
			"LeaveSuccessful" => "false",
			"WaitlistUpdateSuccessful" => "false"
		);
		echo json_encode($response);
		$megaDB->autocommit(true);
		exit();
	}
	else 
	{
		if($leaveClass->affected_rows)
		{
			$stack["LeaveSuccessful"] = "true";
			$stack["WaitlistUpdateSuccessful"] = "false";

			if(!empty($_POST['className']) && !empty($_POST['classDate']))
			{
			
				$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left your class";
				$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left the class : " . $_POST['className'] . "\n";
				$message .= "Class Date: " . $_POST['classDate'] . "\n";
				$message = wordwrap($message,70);
				
				$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
				INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
				INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
				WHERE CLASS_DETAILS.CLASS_ID = ?");
				$getEmails->bind_param("i",$_POST['classID']);
				if($getEmails->execute())
				{
					$result = $getEmails->get_result();
					if($result->num_rows)
					{
						while($users = $result->fetch_assoc())
						{
							$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
							if(in_array("CLASSES", $notifications)) 
							{
								mail($users['USER_EMAIL'],$subject,$message);
								$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
								$type = "LEAVE_CLASS";
								$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
								$email_log->execute();
							}
							else
							{
								$stack["email"] = "error";
							}
							
						}
						$stack["email"] = "sent";
					}
					else
					{
						$stack["email"] = "none found";
					}
				}
				else
				{
					$stack["email"] = "execute error";
				}
			}
			else
			{
				$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
				$getClassDeets->bind_param("i",$_POST['classID']);
				if($getClassDeets->execute())
				{
					$getClassDeets->bind_result($name,$date);       
					$getClassDeets->store_result();
					while($getClassDeets->fetch())
					{
						$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left your class";
						$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left the class : " . $name . "\n";
						$message .= "Class Date: " . $date . "\n";
						$message = wordwrap($message,70);
						
						$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
						INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
						INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
						WHERE CLASS_DETAILS.CLASS_ID = ?");
						$getEmails->bind_param("i",$_POST['classID']);
						if($getEmails->execute())
						{
							$result = $getEmails->get_result();
							if($result->num_rows)
							{
								while($users = $result->fetch_assoc())
								{
									$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
									if(in_array("CLASSES", $notifications)) 
									{
										mail($users['USER_EMAIL'],$subject,$message);
										$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
										$type = "LEAVE_CLASS";
										$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
										$email_log->execute();
									}
									else
									{
										$stack["email"] = "error";
									}
									
								}
								$stack["email"] = "sent";
							}
							else
							{
								$stack["email"] = "none found";
							}
						}
						else
						{
							$stack["email"] = "execute error";
						}
					}
				}
				else
				{
					$response = array(
						"ErrorA" => "Failed getting class details",
						"ErrorB" => "false"
					);
					echo json_encode($response);
					exit();
				}

			}
		}
		else
		{
			$stack["LeaveSuccessful"] = "false";
			$leaveWait = $megaDB->prepare("DELETE FROM CLASS_WAITLIST WHERE USER_ID = ? AND CLASS_ID = ?");
			$leaveWait->bind_param('ii', $_SESSION['userid'], $_POST['classID']);
			if(!$leaveWait->execute())
			{
				$response = array(
					"ErrorA" => "Delete from Class Waitlist unsuccessful",
					"ErrorB" => "false",
					"LeaveSuccessful" => "false",
					"WaitlistUpdateSuccessful" => "false"
				);
				echo json_encode($response);
				$megaDB->autocommit(true);
				exit();
			}
			else 
			{
				if($leaveWait->affected_rows)
				{
					$stack["WaitlistUpdateSuccessful"] = "true";

					if(!empty($_POST['className']) && !empty($_POST['classDate']))
					{
						$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left your class waitlist";
						$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left the class waitlist for : " . $_POST['className'] . "\n";
						$message .= "Class Date: " . $_POST['classDate'] . "\n";
						$message = wordwrap($message,70);
						
						$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
						INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
						INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
						WHERE CLASS_DETAILS.CLASS_ID = ?");
						$getEmails->bind_param("i",$_POST['classID']);
						if($getEmails->execute())
						{
							$result = $getEmails->get_result();
							if($result->num_rows)
							{
								while($users = $result->fetch_assoc())
								{
									$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
									if(in_array("WAITLIST", $notifications)) 
									{
										mail($users['USER_EMAIL'],$subject,$message);
										$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
										$type = "LEAVE_WAITLIST";
										$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
										$email_log->execute();
									}
									else
									{
										$stack["email"] = "error";
									}
									
								}
								$stack["email"] = "sent";
							}
							else
							{
								$stack["email"] = "none found";
							}
						}
						else
						{
							$stack["email"] = "execute error";
						}
					}
					else
					{
						$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
						$getClassDeets->bind_param("i",$_POST['classID']);
						if($getClassDeets->execute())
						{
							$getClassDeets->bind_result($name,$date);       
							$getClassDeets->store_result();
							while($getClassDeets->fetch())
							{
								$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left your class waitlist";
								$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " left the class waitlist for : " . $name . "\n";
								$message .= "Class Date: " . $date . "\n";
								$message = wordwrap($message,70);
								
								$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
								INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
								INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
								WHERE CLASS_DETAILS.CLASS_ID = ?");
								$getEmails->bind_param("i",$_POST['classID']);
								if($getEmails->execute())
								{
									$result = $getEmails->get_result();
									if($result->num_rows)
									{
										while($users = $result->fetch_assoc())
										{
											$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
											if(in_array("WAITLIST", $notifications)) 
											{
												mail($users['USER_EMAIL'],$subject,$message);
												$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
												$type = "LEAVE_WAITLIST";
												$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
												$email_log->execute();
											}
											else
											{
												$stack["email"] = "error";
											}
											
										}
										$stack["email"] = "sent";
									}
									else
									{
										$stack["email"] = "none found";
									}
								}
								else
								{
									$stack["email"] = "execute error";
								}
							}
						}
						else
						{
							$response = array(
								"ErrorA" => "Failed getting class details",
								"ErrorB" => "false"
							);
							echo json_encode($response);
							exit();
						}
					}
				}
				else 
				{
					$stack["WaitlistUpdateSuccessful"] = "false";
				}
			}
			$leaveWait->close();
		}
		$leaveAnalytics = $megaDB->prepare("INSERT INTO JOIN_LEAVE_ANALYTICS (USER_ID, CLASS_ID, ACTION) VALUES (?,?,?)");
		$left = "Left Class";
		$leaveAnalytics->bind_param('iis', $_SESSION['userid'], $_POST['classID'], $left);
		if(!$leaveAnalytics->execute())
		{
			$response = array(
				"ErrorA" => "Analytics Fail",
				"ErrorB" => "false"
			);
			echo json_encode($response);
			$megaDB->autocommit(true);
			exit();
		}
		$leaveAnalytics->close();
		$megaDB->commit();
	}
	$leaveClass->close();
	
	$classMax = $megaDB->prepare("SELECT CLASS_CAPACITY, MAXIMUM_SIZE FROM CLASS_DETAILS WHERE CLASS_ID = ?");
	$classMax->bind_param('i', $_POST['classID']);
	if(!$classMax->execute())
	{
		$response = array(
					"ErrorA" => "false",
					"ErrorB" => "Delete from Class Waitlist unsuccessful"
				);
		$response["WaitlistUpdateSuccessful"] = $stack["WaitlistUpdateSuccessful"];
		$response["LeaveSuccessful"] = $stack["LeaveSuccessful"];
		echo json_encode($response);
		$megaDB->autocommit(true);
		exit();
	}
	else 
	{
		$classMax->bind_result($CAP,$MAX);       
		$classMax->store_result();
		$classMax->fetch();
		if($CAP < $MAX)
		{
			$findUser = $megaDB->prepare("SELECT USER_ID, CLASS_ID FROM CLASS_WAITLIST WHERE CLASS_ID = ? ORDER BY ID LIMIT 1"); // We should get the first person who joined. Might need to add that in
			$findUser->bind_param('i',$_POST['classID']);
			if(!$findUser->execute())
			{
				$response = array(
					"ErrorA" => "false",
					"ErrorB" => "Delete from Class Waitlist unsuccessful"
				);
				$response["WaitlistUpdateSuccessful"] = $stack["WaitlistUpdateSuccessful"];
				$response["LeaveSuccessful"] = $stack["LeaveSuccessful"];
				echo json_encode($response);
				$megaDB->autocommit(true);
				exit();
			}
			else 
			{
				$findUser->bind_result($waitlistUserid,$waitlistClassid); 
				$findUser->store_result();
				$findUser->fetch();
				if(empty($waitlistUserid) || empty($waitlistClassid))
				{
					$response = array(
						"ErrorA" => "false",
						"ErrorB" => "No Users in waitlist"
					);
					$response["WaitlistUpdateSuccessful"] = $stack["WaitlistUpdateSuccessful"];
					$response["LeaveSuccessful"] = $stack["LeaveSuccessful"];
					echo json_encode($response);
					$megaDB->autocommit(true);
					exit();
				}
				else 
				{
					$joinClass = $megaDB->prepare("INSERT INTO USER_CLASSES(USER_ID, CLASS_ID) VALUES (?,?)");
					$joinClass->bind_param('ii', $waitlistUserid, $waitlistClassid);
					if(!$joinClass->execute())
					{
						$response = array(
							"ErrorA" => "false",
							"ErrorB" => "Error pushing Waitlist user into class"
						);
						$response["WaitlistUpdateSuccessful"] = $stack["WaitlistUpdateSuccessful"];
						$response["LeaveSuccessful"] = $stack["LeaveSuccessful"];
						echo json_encode($response);
						$megaDB->autocommit(true);
						exit();
					}
					else 
					{
						$deleteWait = $megaDB->prepare("DELETE FROM CLASS_WAITLIST WHERE USER_ID = ? AND CLASS_ID = ?");
						$deleteWait->bind_param('ii', $waitlistUserid, $waitlistClassid);
						if(!$deleteWait->execute())
						{
							$response = array(
								"ErrorA" => "false",
								"ErrorB" => "Error deleting from waitlist"
							);
							$response["WaitlistUpdateSuccessful"] = $stack["WaitlistUpdateSuccessful"];
							$response["LeaveSuccessful"] = $stack["LeaveSuccessful"];
							echo json_encode($response);
							$megaDB->autocommit(true);
							exit();
						}
						$deleteWait->close();
						
						
						$getUser = $megaDB->prepare("SELECT CONCAT(`USER_FIRST_NAME`,' ',`USER_LAST_NAME`) AS USER FROM USER WHERE USER_ID = ?");
						$getUser->bind_param('i',$waitlistUserid);
						if(!$getUser->execute())
						{
							$stack["getusername"] = "error";
							echo json_encode($response);
							$megaDB->autocommit(true);
							exit();
						}
						else 
						{
							$getUser->bind_result($userName);       
							$getUser->store_result();
							$getUser->fetch();
							
							if(!empty($_POST['className']) && !empty($_POST['classDate']))
							{
								$subject = $userName . " joined your class";
								$message = $userName . " joined the class : " . $_POST['className'] . " from the waitlist.\n";
								$message .= "Class Date: " . $_POST['classDate'] . "\n";
								$message = wordwrap($message,70);
								$getUser->close();
								
								$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
								INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
								INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
								WHERE CLASS_DETAILS.CLASS_ID = ?");
								$getEmails->bind_param("i",$_POST['classID']);
								if($getEmails->execute())
								{
									$result = $getEmails->get_result();
									if($result->num_rows)
									{
										while($users = $result->fetch_assoc())
										{
											$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
											if(in_array("WAITLIST", $notifications)) 
											{
												mail($users['USER_EMAIL'],$subject,$message);
												$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
												$type = "JOIN_WAITLIST";
												$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
												$email_log->execute();
											}
											else
											{
												$stack["email"] = "error";
											}
											
										}
										$stack["email"] = "sent";
									}
									else
									{
										$stack["email"] = "none found";
									}
								}
								else
								{
									$stack["email"] = "execute error";
								}
							}
							else
							{
								$subject = $userName . " joined your class";
								$message = $userName;
								$getUser->close();

								$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
								$getClassDeets->bind_param("i",$_POST['classID']);
								if($getClassDeets->execute())
								{
									$getClassDeets->bind_result($name,$date);       
									$getClassDeets->store_result();
									while($getClassDeets->fetch())
									{	
										$message .=  " joined the class : " . $name . " from the waitlist.\n";
										$message .= "Class Date: " .$date . "\n";
										$message = wordwrap($message,70);
										
										$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
										INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
										INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
										WHERE CLASS_DETAILS.CLASS_ID = ?");
										$getEmails->bind_param("i",$_POST['classID']);
										if($getEmails->execute())
										{
											$result = $getEmails->get_result();
											if($result->num_rows)
											{
												while($users = $result->fetch_assoc())
												{
													$notifications = explode(',', $users['PROFILE_NOTIFICATION_LEVEL'] );
													if(in_array("WAITLIST", $notifications)) 
													{
														mail($users['USER_EMAIL'],$subject,$message);
														$email_log = $megaDB->prepare("INSERT INTO EMAIL_LOGS(SENDER,RECEIVER,TYPE,SUBJECT,MESSAGE) VALUES(?,?,?,?,?)");
														$type = "JOIN_WAITLIST";
														$email_log->bind_param("issss", $_SESSION['userid'],$users['USER_EMAIL'],$type,$subject,$message);
														$email_log->execute();
													}
													else
													{
														$stack["email"] = "error";
													}
													
												}
												$stack["email"] = "sent";
											}
											else
											{
												$stack["email"] = "none found";
											}
										}
										else
										{
											$stack["email"] = "execute error";
										}
									}
								}
								else
								{
									$response = array(
										"ErrorA" => "Failed getting class details",
										"ErrorB" => "false"
									);
									echo json_encode($response);
									$megaDB->autocommit(true);
									exit();
								}
							}
						}
					}
				}
				$joinClass->close();
			}
		}	
	}
}
else
{
	$response = array(
		"ErrorA" => "Not Logged In",
		"ErrorB" => "false",
		"LeaveSuccessful" => "false",
		"WaitlistUpdateSuccessful" => "false"
	);
	echo json_encode($response);
	$megaDB->autocommit(true);
	exit();
}
$stack["ErrorA"] = "false";
$stack["ErrorB"] = "false";




echo json_encode($stack);
$megaDB->commit();
$megaDB->autocommit(true);
?>