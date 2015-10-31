<?php

include("config.php");

$packages = array(
		  array( "id" => 1, "name" => "ChapR Bluetooth Assembled", "price" => 100.00, "active" => true ),
		  array( "id" => 2, "name" => "ChapR WiFi Assembled",      "price" => 115.00, "active" => false),
		  array( "id" => 3, "name" => "ChapR Bluetooth Kit",       "price" => 90.00, "active" => true ),
		  array( "id" => 4, "name" => "ChapR WiFi Kit",            "price" => 105.00, "active" => false),
		  array( "id" => 5, "name" => "ChapR Arduino Programmer",  "price" => 15.00, "active" => true ),
		  array( "id" => 6, "name" => "ChapR Charity Marketing Kit","price" => 0.00, "active" => true )
		  );

$pieces = array(
     array( "id" => 1, "name" => "ChapR Bluetooth",      "abbrev" => "ChapR-BT", "active" => true, "person" => false ),
     array( "id" => 2, "name" => "ChapR WiFi",           "abbrev" => "ChapR-WF", "active" => false, "person" => false),
     array( "id" => 3, "name" => "USB Cable",            "abbrev" => "USBC", "active" => true , "person" => false),
     array( "id" => 4, "name" => "9V Battery",           "abbrev" => "Battery", "active" => true, "person" => false),
     array( "id" => 5, "name" => "ChapR Programmer",     "abbrev" => "ChaprProg", "active" => true , "person" => false),
     array( "id" => 6, "name" => "Cheat Sheet",          "abbrev" => "Cheat", "active" => true, "person" => false),
     array( "id" => 7, "name" => "ChapR Bluetooth Kit",  "abbrev" => "ChapR-BTKit", "active" => true, "person" => false),
     array( "id" => 8, "name" => "ChapR WiFi Kit",       "abbrev" => "ChapR-WFKit", "active" => false, "person" => false),
     array( "id" => 9, "name" => "cRIO Personality",     "abbrev" => "cRIO FRC", "active" => true , "person" => true),
     array( "id" => 10, "name" => "Labview Personality",  "abbrev" => "LV FTC", "active" => true , "person" => true),
     array( "id" => 11, "name" => "RobotC Personality",   "abbrev" => "RobC FTC", "active" => true , "person" => true),
     array( "id" => 12, "name" => "NXT-G Personality",    "abbrev" => "NXT-G FLL", "active" => true , "person" => true),
     array( "id" => 13, "name" => "Assembly Instructions","abbrev" => "Guide", "active" => true, "person" => false),
     array( "id" => 14, "name" => "Business Cards",       "abbrev" => "Cards", "active" => true, "person" => false),
     array( "id" => 15, "name" => "Brochures",            "abbrev" => "Broch", "active" => true, "person" => false),
     array( "id" => 16, "name" => "Charity ChapR Letter", "abbrev" => "Letter", "active" => true, "person" => false)
  );


//
// execute() - a little function for convenience of running sql statements.
//		It takes the $statement, the $connection, and a $prompt that
//		is used to tell the user what went wrong, or right.
//
function execute($connection,$statement,$prompt)
{
     if (mysqli_query($connection,$statement)) {
	  echo "$prompt successful\n";
	  return(true);
     } else {
	  echo "Error during $prompt: " . mysqli_error($connection) . "\n";
	  return(false);
     }
}

function main() 
{
     global $pieces;
     global $packages;
     global $DB_HOST;
     global $DB_DATABASE;
     global $DB_USERNAME;
     global $DB_PASSWORD;

     // we want to know when we use variables that aren't defined

     error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

     // First, create a connection to the database with the given config information

     $con = mysqli_connect($DB_HOST,$DB_USERNAME,$DB_PASSWORD);
     if (mysqli_connect_errno()) {
	  echo "Failed to connect to MySQL: " . mysqli_connect_error() . "\n";
	  return;
     }

     $sql="USE $DB_DATABASE";
     if(!execute($con,$sql,"Set database '$DB_DATABASE' current")) {
	  return;
     }

     foreach($packages as $package) {

       $id = $package["id"];
       $name = $package["name"];
       $price = $package["price"];
       $active = $package["active"]?1:0;

       echo("Name: $name  --- Price: $price  --- Active: $active\n");

       $sql="INSERT INTO packages (PKID, PackageName, Price,  Active) VALUES
                                  ($id,  \"$name\",   $price, $active);";

       if(!execute($con,$sql,"filled database '$DB_DATABASE' with name='$name': price='$price': active=$active")) {
	 return;
       }
     }
     foreach($pieces as $piece) {
	 
       $id = $piece["id"];
       $name = $piece["name"];
       $abbrev = $piece["abbrev"];
       $active = $piece["active"]?1:0;
       $person = $piece["person"]?1:0;
       
       echo("Name: $name  --- Abbrev: $abbrev  --- Active: $active\n");
       
       $sql="INSERT INTO pieces (PID, PieceName,      Abbrev,  Active, IsPersonality) VALUES
                                  ($id, \"$name\", \"$abbrev\", $active, $person);";
       
       if(!execute($con,$sql,"filled database '$DB_DATABASE' with name='$name' --  Abbrev='$abbrev' -- active=$active")) {
	 return;
       }
     }
     
     $sql="INSERT INTO pvp (PKID, PID) VALUES
                                  (1   , 1   ), # ChapR Bluetooth Assembled
                                  (1   , 3   ), # USB Cable
                                  (1   , 4   ), # 9V Battery
                                  (1   , 6   ), # Cheat Sheet
                                  (2   , 2   ), # ChapR WiFi Assembled
                                  (2   , 3   ), # USB Cable
                                  (2   , 4   ), # 9V Battery
                                  (2   , 6   ), # Cheat Sheet
                                  (3   , 7   ), # ChapR Bluetooth Kit
                                  (3   , 3   ), # USB Cable
                                  (3   , 4   ), # 9V Battery
                                  (3   , 6   ), # Cheat Sheet
                                  (4   , 8   ), # ChapR Wifi Kit
                                  (4   , 3   ), # USB Cable
                                  (4   , 4   ), # 9V Battery
                                  (4   , 6   ), # Cheat Sheet
                                  (5   , 5   ), # ChapR Arduino Programmer
                                  (6   , 14   ),# Charity ChapR biz cards
                                  (6   , 15   ),# Charity ChapR brochures
                                  (6   , 16   ) # Chairty ChapR letter
                                  ;";

     if(!execute($con,$sql,"filled database '$DB_DATABASE'")) {
       return;
     }

}

main();

?>