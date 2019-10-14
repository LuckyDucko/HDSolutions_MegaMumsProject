<?php
require_once('DBConnection.php');

function GetCoords($street1, $suburb, $state, $postcode)
{
    $address = $street1 . ' ' . $suburb . ' ' . $state . ' ' . $postcode;
    $address = str_replace(" ", "+", $address);
    $key = 'APIKEYHERE';
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$key";
    $response =file_get_contents($url);
    return $response;
}

function LocateBranch($json)
{
    $megaDBConstructor = new MegaDatabaseConnection;
    $megaDB = $megaDBConstructor->connection();

    $branchLocate = $megaDB->prepare("SELECT BRANCH_COORDINATES, BRANCH_NAME FROM BRANCH"); 
    $branchLocate->execute();
    $branchLocate->bind_result($branchCoords, $branchName); 
    $closest = PHP_INT_MAX;
    $closestName = "";

    while($branchLocate->fetch())
    {
        $branchLatLong = explode(',', $branchCoords);
        $distanceAway = distance($json['results'][0]['geometry']['location']['lat'], $json['results'][0]['geometry']['location']['lng'], $branchLatLong[0], $branchLatLong[1]);
        if($distanceAway < $closest)
        {
            $closest = $distanceAway;
            $closestName = $branchName;
        }
    }

    $branchLocate->close();
    return $closestName;
}

function ParseCoords($json)
{
    return $json['results'][0]['geometry']['location']['lat'].",".$json['results'][0]['geometry']['location']['lng'];
}
?>
