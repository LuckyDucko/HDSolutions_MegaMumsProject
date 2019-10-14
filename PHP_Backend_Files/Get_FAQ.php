<?php
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$getFaq = $megaDB->prepare("SELECT * FROM FAQ");
if($getFaq->execute())
{
    $result = $getFaq->get_result();
    if($result->num_rows)
    {
        while($users = $result->fetch_assoc())
        {
            $list[] = $users;
        }
        echo json_encode($list);
    }
    else
    {
         echo json_encode("none");
    }
}
$getFaq->close();
?>