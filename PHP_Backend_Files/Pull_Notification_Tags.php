<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getTags = $megaDB->prepare("SELECT NOTIFICATION_TAG FROM NOTIFICATION_TAGS
  INNER JOIN USER_PROFILE
    ON NOTIFICATION_TAGS.USER_ID = USER_PROFILE.USER_ID
	WHERE NOTIFICATION_TAGS.USER_ID = ?
      AND FIND_IN_SET(NOTIFICATION_TAGS.CLASSIFICATION,USER_PROFILE.PROFILE_NOTIFICATION_LEVEL)");
$getTags->bind_param('i', $_SESSION['userid']);
if($getTags->execute())
{
	$result = $getTags->get_result();
	if($result->num_rows)
	{
        $tags = "";
		while($tag = $result->fetch_assoc())
		{
            $tags .= $tag["NOTIFICATION_TAG"];
            $tags .= ",";
        }
        $result->free;
        $tags = substr($tags, 0, (strlen($tags)-1));
        
        $stack["TAGS"] = $tags;
        echo json_encode($stack);
	}
	else
	{
        //nothing
		echo json_encode("0");
	}
}
else
{
    //error
	echo json_encode("00");
}

$getTags->close();
?>