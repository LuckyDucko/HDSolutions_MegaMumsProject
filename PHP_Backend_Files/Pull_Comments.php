<?php 
session_start();
require_once('DBConnection.php');

$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();
$getComments = $megaDB->prepare("SELECT * FROM COMMENTS_VIEW WHERE CLASS_ID = ? AND PARENT_COMMENT = 0 ORDER BY DATE_CREATED ASC");
$getComments->bind_param("i",$_POST['classID']);
if($getComments->execute())
{
    //$getComments->bind_result($commentid,$classid,$userid, $message, $datecreated, $parentcomment, $facilitator);      
    //$getComments->store_result();
    $unsortedComments = array();
    $unsortedReplies = array();
    $result = $getComments->get_result();
    if($result->num_rows)
    {
        while($comment = $result->fetch_assoc())
        {
            $unsortedComments[] = $comment;
        }
        $getComments->close();

        $getReplied = $megaDB->prepare("SELECT * FROM COMMENTS_VIEW WHERE CLASS_ID = ? AND PARENT_COMMENT != 0  ORDER BY DATE_CREATED ASC");
        $getReplied->bind_param("i",$_POST['classID']);
        if($getReplied->execute())
        {
            $result2 = $getReplied->get_result();
            if($result2->num_rows)
            {
                while($comment = $result2->fetch_assoc())
                {
                    $unsortedReplies[] = $comment;
                }
            }
            else 
            {
                //no replies
            }
        }
        else 
        {
            //failed to get replies
        }

        $allComments = array();
        for($i = 0; $i < count($unsortedComments); $i++)
        {
            $allComments[] = $unsortedComments[$i];
            for($j = 0; $j < count($unsortedReplies); $j++)
            {
                if($unsortedComments[$i]["COMMENT_ID"] == $unsortedReplies[$j]["PARENT_COMMENT"])
                {
                    $allComments[] = $unsortedReplies[$j];
                }
            }
        }
        echo json_encode($allComments);
    }
    else 
    {
        $stack["response"] = "No Comments";
        echo json_encode($stack);
    }
}
else
{
    $stack["response"] = "Success";
    echo json_encode($stack);
}
?>
