<?php
session_start();
require_once('Notification_Hub.php');

$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$megaDB->autocommit(false);

$classID = $_POST['classID'];
$stack = array();

if(isset($_SESSION['userid']))
{
	$findBranchType = $megaDB->prepare("SELECT BRANCH.BRANCH_TYPE FROM BRANCH
	INNER JOIN CLASS_DETAILS ON BRANCH.BRANCH_NAME = CLASS_DETAILS.ADDRESS_BRANCH
	WHERE CLASS_DETAILS.CLASS_ID = ?");
	$findBranchType->bind_param('i', $classID);
	if(!$findBranchType->execute())
	{
		$response = array(
			"Error" => "Error Finding Branch Type",
			"JoinClass" => "false",
			"JoinWaitlist" => "false"
		);
		echo json_encode($response);
		$megaDB->autocommit(true);
		exit();
	}
	else
	{
		$findBranchType->bind_result($BRANCHTYPE);      // why is this here? 
		$findBranchType->store_result();
		if(!$findBranchType->num_rows)
		{
			$stack["Error"] = "No Type Found";
			$stack["JoinClass"] = "false";
			$stack["JoinWaitlist"] = "false";
		}
		else
		{
			
			$findBranchType->fetch();
			if($BRANCHTYPE == "PAID")
			{
				$date = new DateTime(null, new DateTimeZone('Australia/Sydney'));
				$preFormatted = $date->format('Y-m-d H:i:s');

				$findBranchType->close();
				$getUserMem = $megaDB->prepare("SELECT * FROM MEMBERSHIP WHERE USER_ID = ? AND MEMBERSHIP_END >= ?");
				$getUserMem->bind_param("is",$_SESSION['userid'], $preFormatted);
				$getUserMem->execute();
				$result = $getUserMem->get_result();
				if(!$result->num_rows)
				{
					$stack["Error"] = "Not paid member";
					$stack["JoinClass"] = "false";
					$stack["JoinWaitlist"] = "false";
				}
				else
				{
					$findClass = $megaDB->prepare("SELECT * FROM IN_CLASS WHERE USER_ID = ? AND CLASS_ID = ?");
					$findClass->bind_param('ii', $_SESSION['userid'], $classID);
					if(!$findClass->execute())
					{
						$response = array(
							"Error" => "Error Finding class",
							"JoinClass" => "false",
							"JoinWaitlist" => "false"
						);
						echo json_encode($response);
						$megaDB->autocommit(true);
						exit();
					}
					else
					{		
						$findClass->bind_result($USERID,$CLASSID);      // why is this here? 
						$findClass->store_result();
						if($findClass->num_rows)
						{
							$stack["Error"] = "in class";
							$stack["JoinClass"] = "false";
							$stack["JoinWaitlist"] = "false";
						}
						else
						{
							$classMax = $megaDB->prepare("SELECT CLASS_CAPACITY, MAXIMUM_SIZE FROM CLASS_DETAILS WHERE CLASS_ID = ?");
							$classMax->bind_param('i', $classID);
							if(!$classMax->execute())
							{
								$response = array(
									"Error" => "Error Finding class 2",
									"JoinClass" => "false",
									"JoinWaitlist" => "false"
								);
								echo json_encode($response);
								$megaDB->autocommit(true);
								exit();
							}
							else 
							{
								$classMax->bind_result($CAP,$MAX);       
								$classMax->store_result();
								$classMax->fetch();
								if($CAP >= $MAX && $MAX != 0)
								{
									$joinWait = $megaDB->prepare("INSERT INTO CLASS_WAITLIST (USER_ID, CLASS_ID) VALUES (?,?)");
									$joinWait->bind_param('ii', $_SESSION['userid'], $classID);
									if(!$joinWait->execute())
									{
										$response = array(
											"Error" => "Error joining waitlist",
											"JoinClass" => "false",
											"JoinWaitlist" => "false"
										);
										echo json_encode($response);
										$megaDB->autocommit(true);
										exit();
									}
									else
									{
										
										if(!empty($_POST['className']) && !empty($_POST['classDate']))
										{
										
											$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your waitlist";
											$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $_POST['className'] . "'s waitlist.\n";
											$message .= "Class Date: " . $_POST['classDate'] . "\n";
											$message = wordwrap($message,70);
											
											$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
											INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
											INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
											WHERE CLASS_DETAILS.CLASS_ID = ?");
											$getEmails->bind_param("i",$classID);
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
											$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
											$getClassDeets->bind_param("i",$classID);
											if($getClassDeets->execute())
											{
												$getClassDeets->bind_result($name,$date);       
												$getClassDeets->store_result();
												while($getClassDeets->fetch())
												{
													$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your waitlist";
													$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $name. "'s waitlist.\n";
													$message .= "Class Date: " . $date . "\n";
													$message = wordwrap($message,70);
													
													$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
													INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
													INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
													WHERE CLASS_DETAILS.CLASS_ID = ?");
													$getEmails->bind_param("i",$classID);
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
												$stack["Error"] = "Error getting class details";
												$stack["JoinClass"] = "false";
												$stack["JoinWaitlist"] = "false";
											}											
										}
													
										$stack["Error"] = "false";
										$stack["JoinClass"] = "false";
										$stack["JoinWaitlist"] = "true";
									}
									$joinWait->close();
									$classMax->close();
								}
								else
								{
									$joinClass = $megaDB->prepare("INSERT INTO USER_CLASSES(USER_ID, CLASS_ID) VALUES (?,?)");
									$joinClass->bind_param('ii', $_SESSION['userid'], $classID);
									if(!$joinClass->execute())
									{
										$response = array(
											"Error" => "Error joining class",
											"JoinClass" => "false",
											"JoinWaitlist" => "false"
										);
										echo json_encode($response);
										$megaDB->rollback();
										$megaDB->autocommit(true);
										exit();
									}
									else
									{
										$stack["JoinClass"] = "true";
										$stack["JoinWaitlist"] = "false";

										$BaseMessage = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " just joined as part of class";
										$MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

										$DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
										if($DatabaseCheck != "success")
										{
											$stack["Error"] = $DatabaseCheck;
										}
										else
										{
											$pushtag = 'u' . $classID;
											$PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $pushtag);

											if($PushNotificationTagCheck != "success")
											{
												$stack["Error"] = $PushNotificationTagCheck;
											}
											else
											{
												$AzureCheck = $MegaNotificationHub->SendRemoteNotificationAzure($pushtag);

												if($AzureCheck != "success")
												{
													$stack["Error"] = $AzureCheck;
												}
												else
												{
													$stack["Error"] = "false";
												}
											}
										}
										
										if(!empty($_POST['className']) && !empty($_POST['classDate']))
										{
											$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your class";
											$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $_POST['className'] . "\n";
											$message .= "Class Date: " . $_POST['classDate'] . "\n";
											$message = wordwrap($message,70);
											
											$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
											INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
											INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
											WHERE CLASS_DETAILS.CLASS_ID = ?");
											$getEmails->bind_param("i",$classID);
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
															$type = "JOIN_CLASS";
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
											$getClassDeets->bind_param("i",$classID);
											if($getClassDeets->execute())
											{
												$getClassDeets->bind_result($name,$date);       
												$getClassDeets->store_result();
												while($getClassDeets->fetch())
												{
													$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your class";
													$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $name . "\n";
													$message .= "Class Date: " . $date . "\n";
													$message = wordwrap($message,70);
													
													$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
													INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
													INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
													WHERE CLASS_DETAILS.CLASS_ID = ?");
													$getEmails->bind_param("i",$classID);
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
																	$type = "JOIN_CLASS";
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
												$stack["Error"] = "Error getting class details";
												$stack["JoinClass"] = "false";
												$stack["JoinWaitlist"] = "false";
											}		
										}
									}
									
									$joinClass->close();
								}
							}
						}
						
						$findClass->close();
						
						$joinAnalytics = $megaDB->prepare("INSERT INTO JOIN_LEAVE_ANALYTICS (USER_ID, CLASS_ID, ACTION) VALUES (?,?,?)");
						$joined = "Joined Class";
						$joinAnalytics->bind_param('iis', $_SESSION['userid'], $classID, $joined);
						if(!$joinAnalytics->execute())
						{
							$response = array(
								"Error" => "Analytics Fail"
							);
							echo json_encode($response);
							$megaDB->rollback();
							$megaDB->autocommit(true);
							exit();
						}
						$joinAnalytics->close();
					}
					$getUserMem->close();
				}
			}
			else
			{
				
				$findClass = $megaDB->prepare("SELECT * FROM IN_CLASS WHERE USER_ID = ? AND CLASS_ID = ?");
				$findClass->bind_param('ii', $_SESSION['userid'], $classID);
				if(!$findClass->execute())
				{
					$response = array(
						"Error" => "Error Finding class",
						"JoinClass" => "false",
						"JoinWaitlist" => "false"
					);
					echo json_encode($response);
					$megaDB->autocommit(true);
					exit();
				}
				else
				{		
					$findClass->bind_result($USERID,$CLASSID);      // why is this here? 
					$findClass->store_result();
					if($findClass->num_rows)
					{
						$stack["Error"] = "in class";
						$stack["JoinClass"] = "false";
						$stack["JoinWaitlist"] = "false";
					}
					else
					{
						$classMax = $megaDB->prepare("SELECT CLASS_CAPACITY, MAXIMUM_SIZE FROM CLASS_DETAILS WHERE CLASS_ID = ?");
						$classMax->bind_param('i', $classID);
						if(!$classMax->execute())
						{
							$response = array(
								"Error" => "Error Finding class 2",
								"JoinClass" => "false",
								"JoinWaitlist" => "false"
							);
							echo json_encode($response);
							$megaDB->autocommit(true);
							exit();
						}
						else 
						{
							$classMax->bind_result($CAP,$MAX);       
							$classMax->store_result();
							$classMax->fetch();
							if($CAP >= $MAX && $MAX != 0)
							{
								$joinWait = $megaDB->prepare("INSERT INTO CLASS_WAITLIST (USER_ID, CLASS_ID) VALUES (?,?)");
								$joinWait->bind_param('ii', $_SESSION['userid'], $classID);
								if(!$joinWait->execute())
								{
									$response = array(
										"Error" => "Error joining waitlist",
										"JoinClass" => "false",
										"JoinWaitlist" => "false"
									);
									echo json_encode($response);
									$megaDB->autocommit(true);
									exit();
								}
								else
								{
									if(!empty($_POST['className']) && !empty($_POST['classDate']))
									{
									
										$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your waitlist";
										$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $_POST['className'] . "'s waitlist.\n";
										$message .= "Class Date: " . $_POST['classDate'] . "\n";
										$message = wordwrap($message,70);
										
										$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
										INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
										INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
										WHERE CLASS_DETAILS.CLASS_ID = ?");
										$getEmails->bind_param("i",$classID);
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
										$getClassDeets = $megaDB->prepare("SELECT CLASS_NAME, CLASS_DATE FROM ALL_CLASSES WHERE CLASS_ID = ?");
										$getClassDeets->bind_param("i",$classID);
										if($getClassDeets->execute())
										{
											$getClassDeets->bind_result($name,$date);       
											$getClassDeets->store_result();
											while($getClassDeets->fetch())
											{
												$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your waitlist";
												$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $name. "'s waitlist.\n";
												$message .= "Class Date: " . $date . "\n";
												$message = wordwrap($message,70);
												
												$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
												INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
												INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
												WHERE CLASS_DETAILS.CLASS_ID = ?");
												$getEmails->bind_param("i",$classID);
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
											$stack["Error"] = "Error getting class details";
											$stack["JoinClass"] = "false";
											$stack["JoinWaitlist"] = "false";
										}											
									}

									$stack["Error"] = "false";
									$stack["JoinClass"] = "false";
									$stack["JoinWaitlist"] = "true";
								}
								$joinWait->close();
								$classMax->close();
							}
							else
							{
								$joinClass = $megaDB->prepare("INSERT INTO USER_CLASSES(USER_ID, CLASS_ID) VALUES (?,?)");
								$joinClass->bind_param('ii', $_SESSION['userid'], $classID);
								if(!$joinClass->execute())
								{
									$response = array(
										"Error" => "Error joining class",
										"JoinClass" => "false",
										"JoinWaitlist" => "false"
									);
									echo json_encode($response);
									$megaDB->rollback();
									$megaDB->autocommit(true);
									exit();
								}
								else
								{
									$stack["JoinClass"] = "true";
									$stack["JoinWaitlist"] = "false";

									$BaseMessage = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " just joined as part of class";
									$MegaNotificationHub = new MegaNotification($BaseMessage, "CLASSES");

									$DatabaseCheck = $MegaNotificationHub->AddNotificationToDatabase($classID);
									if($DatabaseCheck != "success")
									{
										$stack["Error"] = $DatabaseCheck;
									}
									else
									{
										$pushtag = 'u' . $classID;
										$PushNotificationTagCheck = $MegaNotificationHub->PushUserNotificationTags($_SESSION['userid'], $pushtag);

										if($PushNotificationTagCheck != "success")
										{
											$stack["Error"] = $PushNotificationTagCheck;
										}
										else
										{
											$AzureCheck = $MegaNotificationHub->SendRemoteNotificationAzure($pushtag);

											if($AzureCheck != "success")
											{
												$stack["Error"] = $AzureCheck;
											}
											else
											{
												$stack["Error"] = "false";
											}
										}
									}
									
									if(!empty($_POST['className']) && !empty($_POST['classDate']))
									{
										$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your class";
										$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $_POST['className'] . "\n";
										$message .= "Class Date: " . $_POST['classDate'] . "\n";
										$message = wordwrap($message,70);
										
										$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
										INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
										INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
										WHERE CLASS_DETAILS.CLASS_ID = ?");
										$getEmails->bind_param("i",$classID);
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
														$type = "JOIN_CLASS";
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
										$getClassDeets->bind_param("i",$classID);
										if($getClassDeets->execute())
										{
											$getClassDeets->bind_result($name,$date);       
											$getClassDeets->store_result();
											while($getClassDeets->fetch())
											{
												$subject = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined your class";
												$message = $_SESSION['fname'] . ' '. $_SESSION['lname'] . " joined the class : " . $name . "\n";
												$message .= "Class Date: " . $date . "\n";
												$message = wordwrap($message,70);
												
												$getEmails = $megaDB->prepare("SELECT USER_EMAIL, PROFILE_NOTIFICATION_LEVEL FROM USER 
												INNER JOIN CLASS_DETAILS ON USER.USER_ID = CLASS_DETAILS.USER_ID
												INNER JOIN USER_PROFILE ON USER.USER_ID = USER_PROFILE.USER_ID
												WHERE CLASS_DETAILS.CLASS_ID = ?");
												$getEmails->bind_param("i",$classID);
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
																$type = "JOIN_CLASS";
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
											$stack["Error"] = "Error getting class details";
											$stack["JoinClass"] = "false";
											$stack["JoinWaitlist"] = "false";
										}		
									}
								}
								
								$joinClass->close();
							}
						}
					}
					
					$findClass->close();
					
					$joinAnalytics = $megaDB->prepare("INSERT INTO JOIN_LEAVE_ANALYTICS (USER_ID, CLASS_ID, ACTION) VALUES (?,?,?)");
					$joined = "Joined Class";
					$joinAnalytics->bind_param('iis', $_SESSION['userid'], $classID, $joined);
					if(!$joinAnalytics->execute())
					{
						$response = array(
							"Error" => "Analytics Fail"
						);
						echo json_encode($response);
						$megaDB->rollback();
						$megaDB->autocommit(true);
						exit();
					}
					$joinAnalytics->close();
				}
			}
			
		}
	}
}
else
{
	$response = array(
		"Error" => "Not Logged In",
		"JoinClass" => "false",
		"JoinWaitlist" => "false"
	);
	echo json_encode($response);
	$megaDB->autocommit(true);
	exit();
}

$megaDB->commit();
$megaDB->autocommit(true);
echo json_encode($stack);

?>