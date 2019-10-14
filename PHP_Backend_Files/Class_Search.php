<?php 
session_start();
require_once('DBConnection.php');
$megaDBConstructor = new MegaDatabaseConnection;
$megaDB = $megaDBConstructor->connection();


$searchQuery = "%{$_POST['search']}%";

$sql = "SELECT * FROM CLASS_DETAILS"; // initial sql
$BindParameterVariables = array(); // Array for all the parameter variables we will be using
$BindParameterType = ""; // string which will contain all the types for the variables


$sql .= " WHERE CLASS_NAME LIKE ?"; // Add on the extra sql we require (Make sure space beforehand)
$BindParameterVariables[] = $searchQuery; // we add our variable into the array for later use.
$BindParameterType .= "s"; // we append the type of that variable into 

$sql .= " OR ADDRESS_SUBURB LIKE ?"; // Add on the extra sql we require (Make sure space beforehand)
$BindParameterVariables[] = $searchQuery; // we add our variable into the array for later use.
$BindParameterType .= "s"; // we append the type of that variable into 

if(!empty($_POST['branchInput'])) // And again
{
	$sql .= " AND ADDRESS_BRANCH = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["branchInput"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['classFrom'])) // And again
{
	$sql .= " AND CLASS_DATE >= ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["classFrom"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['classTo'])) // And again
{
	$sql .= " AND CLASS_DATE <= ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["classTo"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['classDate'])) // And again
{
	$sql .= " AND CLASS_DATE = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["classDate"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['priceFrom'])) // And again
{
	$sql .= " AND CLASS_COST >= ?"; // adding on extra sql
	$priceFrom = preg_replace('/\D/', '', $_POST['priceFrom']);
	$BindParameterVariables[] = $priceFrom; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['priceTo'])) // And again
{
	$sql .= " AND CLASS_COST <= ?"; // adding on extra sql
	$priceTo = preg_replace('/\D/', '', $_POST['priceTo']);
	$BindParameterVariables[] = $priceTo; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['Address'])) // And again
{
	$sql .= " AND ADDRESS_LINE_ONE LIKE ?"; // adding on extra sql
	$address = "%{$_POST["Address"]}%";
	$BindParameterVariables[] = $address; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['Suburb'])) // And again
{
	$sql .= " AND ADDRESS_SUBURB = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["Suburb"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['State']) && ($_POST['State']) != "") // And again
{
	$sql .= " AND ADDRESS_STATE = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["State"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['Postcode'])) // And again
{
	$sql .= " AND ADDRESS_POSTCODE = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST["Postcode"]; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['facil'])) // And again
{
	$sql .= " AND CONCAT(USER_FIRST_NAME, ' ', USER_LAST_NAME) LIKE ?"; // adding on extra sql
	$facil = "%{$_POST['facil']}%";
	$BindParameterVariables[] = $facil; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['childFriendly']) && ($_POST['childFriendly']) != "") // And again
{
	$sql .= " AND CLASS_CHILD_FRIENDLY = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST['childFriendly']; // adding the variable into array
	$BindParameterType .= "i"; // appending the type
}

if(!empty($_POST['difficulty']) && ($_POST['difficulty']) != "") // And again
{
	$sql .= " AND CLASS_DIFFICULTY = ?"; // adding on extra sql
	$BindParameterVariables[] = $_POST['difficulty']; // adding the variable into array
	$BindParameterType .= "s"; // appending the type
}

if(!empty($_POST['duration'])) // And again
{
	if($_POST['duration'] == '120')
	{
		$sql .= " AND (TIMEDIFF(convert(END_TIME, TIME), convert(START_TIME, TIME))) >= time(?)"; // adding on extra sql
		$BindParameterVariables[] = '2:00:00'; // adding the variable into array
		$BindParameterType .= "s";
	}
	else if ($_POST['duration'] == '90')
	{
		$sql .= " AND (TIMEDIFF(convert(END_TIME, TIME), convert(START_TIME, TIME))) = time(?)"; // adding on extra sql
		$BindParameterVariables[] = '1:30:00'; // adding the variable into array
		$BindParameterType .= "s";
	}
	else if ($_POST['duration'] == '60')
	{
		$sql .= " AND (TIMEDIFF(convert(END_TIME, TIME), convert(START_TIME, TIME))) = time(?)"; // adding on extra sql
		$BindParameterVariables[] = '1:00:00'; // adding the variable into array
		$BindParameterType .= "s";
	}
	else
	{
		$sql .= " AND (TIMEDIFF(convert(END_TIME, TIME), convert(START_TIME, TIME))) = time(?)"; // adding on extra sql
		$BindParameterVariables[] = "0:{$_POST['duration']}:00"; // adding the variable into array
		$BindParameterType .= "s";
	}
}

$sql .= " ORDER BY CLASS_DATE ASC, STR_TO_DATE(concat('01,01,2018 ',START_TIME), '%d,%m,%Y %h:%i %p') ASC"; //order results by class date

//now we need to make bindparamarguments contain both the type, and the variables, in that order. 
//the & makes it by reference. 
//bind param requires by reference as it can be changed in the one PHP file, and by reference means it equals whatever 
//the variable equals, instead of by value which cannot be changed without rebinding the variable over and over
//However, as we only use it once, it doesnt affect us, except we need to use that &. 

$BindParamArguments[] = & $BindParameterType; // 1st part of the argument array is the type string, such as "sssss", remembering the &
 
// 2nd part of the argument array is adding on each variable seperately. remembering that &.
for($i = 0; $i < count($BindParameterVariables); $i++)  
{
  $BindParamArguments[] = & $BindParameterVariables[$i];
}

//BindParamArguments should now contain an array that has all the types as [0], 
//and then each parameter variable for those types as [1], [2] so on so forth. 
//example [0] = "ss" [1] = $searchQuery [2] = $_POST["branchInput"]



$getClass = $megaDB->prepare($sql); // we prepare the sql statement we kept appending on.
if($getClass === false) 
{
	trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $megaDB->errno . ' ' . $megaDB->error, E_USER_ERROR); // this will tell us if we appended wrong
}
call_user_func_array(array($getClass, 'bind_param'), $BindParamArguments); 
// all that setup goes into this, where it takes our getclass, and calls the function 'bind_param'|| eg $getClass->bind_param(),
// with the arguments contained in $BindParamArguments || eg $getClass->bind_param("ss", $searchQuery, $_POST("branchInput"))

//below is all normal stuff

if($getClass->execute())
{
	$result = $getClass->get_result();
	if($result->num_rows)
	{
		while($class = $result->fetch_assoc())
		{
			$classes[] = $class;
		}
		echo json_encode($classes);
	}
	else
	{
		echo json_encode("none");
	}
}
else
{
	echo json_encode("execute error");
}

$getClass->close();
?>