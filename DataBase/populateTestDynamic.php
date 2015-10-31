<?php

include("config.php");
                
$customers = array(
		   array( "cid" => 1,
			  "fname" => "Bob",
			  "lname" => "Johnson", 
			  "email" => "bobJohnson@gmail.com", 
			  "title" => "Warlord", 
			  "phone" => "8675309", 
			  "teams" => "666",
			  "cnotes" => "Youre a poopy head",
			  "teams" => "666",
			  "metdate" => 0,
			  "street1" => "yo mamas house",
			  "street2" => "the north pole",
			  "city" => "Bruno",
			  "state" => "Mars",
			  "zip" => "12345",
			  "country" => "North America")
		   );

$orders = array(
                   array( "oid" => 1,
                          "cid" => 1,
                          "ordereddate" => 0,
                          "onotes" => "You are still a poopy head",
                          "isexpedited" => 0,
                          "requestedpay" => 1,
                          "paiddate" => 0,
                          "shippeddate" => 0,
                          "carrier" => "Joey?",
                          "trackingnum" => 111111111111111111111111111111111111,
                          "wasreceived" => 1,
                          "wascanceled" => 0,
                          "expeditefee" => 12.34,
                          "shippingfee" => 9999.99)
                   );

$items = array(
		array( "iid" => 1,
		       "oid" => 1,
		       "pkid" => 1,
		       "personality" => 1,
                       "price" => 11.11),
		array( "iid" => 2,
                       "oid" => 1,
                       "pkid" => 3,
                       "personality" => 1,
                       "price" => 21.00),
		array( "iid" => 3,
                       "oid" => 1,
                       "pkid" => 5,
                       "personality" => 3,
                       "price" => 31.11)
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
     global $customers;
     global $orders;
     global $items;
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


     foreach($customers as $customer) {
	 
       $cid = $customer["cid"];
       $fname = $customer["fname"];
       $lname = $customer["lname"];
       $email = $customer["email"];
       $title = $customer["title"];
       $phone = $customer["phone"];
       $teams = $customer["teams"];
       $cnotes = $customer["cnotes"];
       $metdate = $customer["metdate"];
       $street1 = $customer["street1"];
       $street2 = $customer["street2"];
       $city = $customer["city"];
       $state = $customer["state"];
       $zip = $customer["zip"];
       $country = $customer["country"];
       
       $sql="INSERT INTO customers (CID,   FirstName, LastName , Email,     Title,     Phone,     Teams,  CustomerNotes, MetDate,     Street1,    Street2, City, State, Zip, Country) VALUES
                                  ($cid,\"$fname\",\"$lname\",\"$email\",\"$title\",\"$phone\",\"$teams\",\"$cnotes\",\"$metdate\",\"$street1\",\"$street2\",\"$city\",\"$state\",\"$zip\",\"$country\" );";
       
       if(!execute($con,$sql,"filled database '$DB_DATABASE'")) {
	 return;
       }
     }

     foreach($orders as $order) {

       $oid = $order["oid"];
       $cid = $order["cid"];
       $ordereddate = $order["ordereddate"];
       $onotes = $order["onotes"];
       $isexpedited = $order["isexpedited"];
       $requestedpay = $order["requestedpay"];
       $paiddate = $order["paiddate"];
       $shippeddate = $order["shippeddate"];
       $carrier = $order["carrier"];
       $trackingnum = $order["trackingnum"];
       $wasreceived = $order["wasreceived"];
       $wascanceled = $order["wascanceled"];
       $expeditefee = $order["expeditefee"];
       $shippingfee = $order["shippingfee"];

       $sql="INSERT INTO orders (OID,CID,OrderedDate,OrderNotes,IsExpedited,RequestedPay,PaidDate,ShippedDate,Carrier,TrackingNum,WasReceived,WasCanceled,ExpediteFee,ShippingFee) VALUES
                                  ($oid,$cid,$ordereddate,\"$onotes\",$isexpedited,$requestedpay,$paiddate,$shippeddate,\"$carrier\",$trackingnum,$wasreceived,$wascanceled,$expeditefee,
                                   $shippingfee);";

       if(!execute($con,$sql,"filled database '$DB_DATABASE'")) {
         return;
       }
     }

     foreach($items as $item) {

       $iid = $item["iid"];
       $oid = $item["oid"];
       $pkid = $item["pkid"];
       $personality = $item["personality"];
       $price = $item["price"];

       $sql="INSERT INTO items (IID, OID, PKID, Personality, Price) VALUES
                                  ($iid,$oid,$pkid,$personality,$price);";

       if(!execute($con,$sql,"filled database '$DB_DATABASE'")) {
         return;
       }
     }

}

main();

?>