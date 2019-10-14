<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();

$branch = $_POST['branchInput']."%";
if(!empty($_POST['branchInput']))
{
	$getBranch = $megaDB->prepare("SELECT BRANCH_NAME FROM BRANCH WHERE BRANCH_NAME LIKE ?");
	$getBranch->bind_param('s',$branch);
	if($getBranch->execute())
	{
		$result = $getBranch->get_result();
		if($result->num_rows)
		{
			while($branch = $result->fetch_assoc())
			{
				$branches[] = $branch["BRANCH_NAME"];
			}
			echo json_encode($branches);
		}
		else
		{
			echo json_encode("none");
		}
	}
	else 
	{
		echo json_encode("error");
	}
} 
else
{
	echo json_encode("branch is empty");
}
?>