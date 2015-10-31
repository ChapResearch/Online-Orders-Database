<?php
  //
  // import.php
  //
  //	This program takes the stdin, reads the data interpreting it
  //	as database records, then inserts each into the database.
  //
  //	Records that are bad (can't be read appropriately) generate
  //	error messages on stderr.
  //

include ("import-rachel.php");
include ("import-eric.php");
include ("dbFunctions.php");

function errorMsg($message)
{
     fputs(STDERR,"******** IMPORT ERROR: $message *********\n");
}

function magicalInsert($row)
{
  // send the appropriate data to insert customer
  $customer = array_slice($row, 0, 14, true);
  print_r("customer");
  print_r($customer);
  $CID = dbInsertNewCustomer($customer);
  // send the appropriate data to insert order
  $order = array_slice($row, 14, 19, true);
  print_r("order");
  print_r($order);
  $OID = dbInsertNewOrder($CID, $order);
  // send the appropriate data to insert items
  $products = array_slice($row, 33, 1, true);
  $items = $products["Products"];
  foreach ($items as $item){
    print_r("item");
    print_r($item);
    dbInsertNewItem($OID, $item);
  }
}

function parseDBLine($record)
{
     $dbRecord = array();

     // note that the fields start off at 0, and they are simply parsed
     // in order

     $field = 0;

     print_r("original record");
     print_r($record);

     list($dbRecord["FirstName"],
	  $dbRecord["LastName"]) = parseName($record[$field++]);
     $dbRecord["Email"]	= parseEmail($record[$field++]);
     $dbRecord["Phone"] = parsePhone($record[$field++]);
     $dbRecord["Title"] = null;
     $dbRecord["CustomerCNotes"] = parseCustomerCNotes($record[$field++]);
     list($dbRecord["Street1"],
	  $dbRecord["Street2"],
	  $dbRecord["City"],
	  $dbRecord["State"],
	  $dbRecord["Zip"]) = parseAddress($record[$field++]);
     $dbRecord["Country"] = "US";
     $dbRecord["AdminCNotes"] = parseAdminCNotes($record[$field++]);
     $dbRecord["OrderedDate"] = $dbRecord["MetDate"] = parseMetDate($record[$field++]);
     $dbRecord["CustomerONotes"] = parseCustomerONotes($record[$field++]);
     $dbRecord["AdminONotes"] = parseAdminONotes($record[$field++]);
     $dbRecord["Charity"] = parseCharity($record[$field++]);
     $dbRecord["RequestedPay"] = parsePaymentRequested($record[$field++]);
     $dbRecord["PaidDate"] = parsePaid($record[$field++],"10/1/2014");
     $dbRecord["ReleasedToShipping"] = parseReleasedToShipping($record[$field++],"10/1/2014");
     $dbRecord["ShippedDate"] = parseShipped($record[$field++], "10/1/14");
     $dbRecord["IsExpedited"] = parseExpedite($record[$field++]);
     $dbRecord["WasCanceled"] = parseCanceled($record[$field++]);
     $dbRecord["InvoiceNumber"] = $dbRecord["InvoiceID"] = $dbRecord["InvoiceURL"] = null;
     $dbRecord["Carrier"] = $dbRecord["TrackingNum"] = $dbRecord["WasReceived"] = null;
     $dbRecord["Discount"] =  $dbRecord["ShippingFee"] = $dbRecord["ExpediteFee"] = null;
     $dbRecord["Products"] = parseProducts($record[$field++]);

     var_dump($dbRecord);
     return $dbRecord;
}

function main()
{
     $data = getData();

     array_shift($data);		// shift off the first "headers" row

     foreach ($data as $record){
       $result = parseDBLine($record);
       magicalInsert($result);
     }
}

//
// getData() - returns an array of rows of incoming TSV data from the ChapR Google
//		spreadsheet.  Each row is an array exploded on tabs.
//
function getData()
{
     // link to the spreadsheet data that is continuously being republished when changes are made

     $url = "https://docs.google.com/spreadsheet/pub?key=0AkOYGoBp6LKvdDVYNU1SR210OE9XR1Ruek5FNnlqeGc&single=true&gid=11&output=txt";

     $con = curl_init();
     
     curl_setopt($con,CURLOPT_URL,$url);
     curl_setopt($con,CURLOPT_HEADER,false);
     curl_setopt($con,CURLOPT_RETURNTRANSFER,true);

     $data = curl_exec($con);
     curl_close($con);

     // now process the data into rows of tab-separated stuff

     $lines = explode("\n",$data);

     $retarray = array();
     foreach($lines as $line) {
	  $retarray[] = explode("\t",$line);
     }

     return($retarray);
}

main();

?>
