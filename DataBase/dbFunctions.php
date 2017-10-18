<?php

// 
// dbErrorMsg() - called to generate error messages for someone.
//		  Currently, just prints out the error message, which will
//		  have the effect of coming out on the browser.
//
function dbErrorMsg($msg)
{
     print("ChapR ERP: \"$msg\"\n");
}

//
// dbConnect() - connect to the mysql database according to the configuration.
//		 Returns the mysql connect if things went OK, null otherwise and
//		 an error message was generated.
//
function dbConnect()
{
     $config = parse_ini_file("/home/rachel/Documents/ordersDB-config.ini");

     // the HOST can be set to a real-live URL to connect to a database somewhere else.
     // but for us, it should be set to either "localhost" or NULL.

     $CHAPRDB_HOST = "localhost";

     // The DATABASE is the name of the whole database, and is changable if you want.

     $CHAPRDB_DATABASE = "chapr";

     // All database accesses are done given a particular database user (just to make
     // things simple) - these are configured here and MUST be configured prior to
     // running anything.

     $CHAPRDB_USERNAME = "chapr";
     $CHAPRDB_PASSWORD = $config['chaprdb_password'];

     $con = mysqli_connect($CHAPRDB_HOST,$CHAPRDB_USERNAME,$CHAPRDB_PASSWORD);
     if (mysqli_connect_errno()) {
	  dbErrorMsg("Failed to connect to MySQL: " . mysqli_connect_error());
	  return(null);
     }

     $sql="USE $CHAPRDB_DATABASE";

     if (!mysqli_query($con,$sql)) {
	  dbErrorMsg("Failed to execute USE for the database: " . mysqli_error());
	  dbClose($con);
	  return(null);
     }

     return($con);
}

function dbClose($con)
{
     mysqli_close($con);
}

/////////////////////////////////////////////////////////////////////////////
// NORMALIZATION ROUTINES
//
//	As data is passed back and forth betweeen this PHP program and the
//	SQL database, it needs to shift form for some pieces of data - like
//	booleans and dates.  The following set of routines deals with this
//	transformation.
/////////////////////////////////////////////////////////////////////////////

//
// dbNormalize() - general-purpose normalization function that takes an array of
//		   fields along with their normalizatoin function, and applies
//		   the function to the fields IF they exist in the $row.  The
//		   incoming $row is changed in place (by reference). No return
//		   value.
//
function dbNormalize(&$row,$normalizationFields)
{
     foreach($normalizationFields as $field => $normFn) {
	  if(array_key_exists($field,$row)) {
	       $row[$field] = $normFn($row[$field]);
	  }
     }
}

//
// dbDatePHP2SQL() - convert back and forth between PHP and SQL time stamps.
// dbDateSQL2PHP()
//
function dbDatePHP2SQL($timestamp)
{
     if($timestamp === false || $timestamp == 0) {
	  // TODO - we MAY want this to be null
	  return(0);
     } else {
	  return(gmdate('Y-m-d H:i:s',$timestamp));
     }
}

function dbDateSQL2PHP($sqltime) 
{
     // dates in SQL can be 0000-00-00 00:00:00
     // and get read back as false (not zero) to php - or maybe we
     // should special case and use null as the no date value

     if($sqltime == "0000-00-00 00:00:00") {
          return(0); 
     } else {
          return(strtotime($sqltime . " GMT"));
     }
}

//
// dbBoolPHP2SQL() - convert back and forth between PHP and SQL boolean values.
// dbBoolSQL2PHP() - PHP boolean comes in as true or false, we define SQL boolean as 1 and 0
//
function dbBoolPHP2SQL($phpbool)
{	
     return(($phpbool)?1:0);
}

function dbBoolSQL2PHP($sqlbool)
{
     return(($sqlbool==1)?true:false);
}

//
// dbOrderNormalize2PHP()    - given a result from a SQL query, convert the appropriate
// dbCustomerNormalize2PHP()   fields to PHP-style values.  Just give it the row array
// dbPackageNormalize2PHP()    and it will normalize.  NOTE that the normalization happens
// dbShippingNormalize2PHP()   IN PLACE on the incoming row array. (pass by reference)
//
function dbOrderNormalize2PHP(&$row)
{
     $fields = array( "OrderedDate"        => "dbDateSQL2PHP",
		      "PaidDate"           => "dbDateSQL2PHP",
		      "ShippedDate"        => "dbDateSQL2PHP",
		      "ReleasedToShipping" => "dbDateSQL2PHP",
		      "Charity"            => "dbBoolSQL2PHP",
		      "IsExpedited"        => "dbBoolSQL2PHP",
		      "WasCanceled"        => "dbBoolSQL2PHP",
		      "WasReceived"        => "dbBoolSQL2PHP",
		      "RequestedPay"       => "dbDateSQL2PHP" );

     dbNormalize($row,$fields);
}

function dbCustomerNormalize2PHP(&$row)
{
     $fields = array( "MetDate" => "dbDateSQL2PHP" );
     dbNormalize($row,$fields);
}

function dbShippingNormalize2PHP(&$row)
{
     $fields = array( "ShippedDate" => "dbDateSQL2PHP" );
     dbNormalize($row,$fields);
}

function dbPackageNormalize2PHP(&$row)
{
     $fields = array( "Active" => "dbBoolSQL2PHP" );
     dbNormalize($row,$fields);
}

//
// dbOrderNormalize2SQL()	- the reverse of the dbOrderNormalize2PHP(), these routines
// dbCustomerNormalize2SQL()	  prepare the data to move from PHP to SQL, converting the
// dbPackageNormalize2SQL()       booleans and dates back to SQL form.
//
function dbOrderNormalize2SQL(&$row)
{
     $fields = array( "OrderedDate"        => "dbDatePHP2SQL",
		      "PaidDate"           => "dbDatePHP2SQL",
		      "ShippedDate"        => "dbDatePHP2SQL",
		      "ReleasedToShipping" => "dbDatePHP2SQL",
		      "Charity"            => "dbBoolPHP2SQL",
		      "IsExpedited"        => "dbBoolPHP2SQL",
		      "WasCanceled"        => "dbBoolPHP2SQL",
		      "WasReceived"        => "dbBoolPHP2SQL",
		      "RequestedPay"       => "dbDatePHP2SQL" );

     dbNormalize($row,$fields);
}

function dbCustomerNormalize2SQL(&$row)
{
     $fields = array( "MetDate" => "dbDatePHP2SQL" );
     dbNormalize($row,$fields);
}

function dbPackageNormalize2SQL(&$row)
{
     $fields = array( "ActiveDate" => "dbBoolPHP2SQL" );
     dbNormalize($row,$fields);
}

///////////////////////////////////////////////////////////////////
// DATABASE AUDIT - ** Wed Oct 22 08:38:33 2014 **
//
//	EJR - I went through the database calls to determine which
//	have the capability to change the database and, therefore,
//	should be audited to alleviate SQL injection exposure.
//
//	NOTE - some of the entries say "no changes made", which
//	causes a "pass" for this audit - however, even in those
//	routines a bad value may cause errors in the SQL - so a
//	second level audit for this type of thing should be done.
//	
///////////////////////////////////////////////////////////////////

//
// dbGetPackagesSummary() - Returns summary data for ALL packages.  The array that is
//			 returned includes rows, each having sumary package data.
//	
//	DATABASE AUDIT - no database changes are made.
//

function dbGetPackagesSummary($sort,$reverse, $all)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     // if $all is true, then show everything, otherwise filter out canceled and shipped packages
     $allSQL = "";
     if(!$all) {
	  $allSQL = "WHERE packages.Active = 1\n";
     }

     if($reverse) {
	  $reverse = "DESC";
     } else {
	  $reverse = "ASC";
     }

     $sql="SELECT packages.PKID,
                  packages.PackageName,
                  packages.Price,
                  packages.Active
                  FROM packages
                  $allSQL
                  ORDER BY $sort $reverse";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPackagesSummary" . mysqli_error($con));
	  $hadError = true;
     } else {
	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	    dbPackageNormalize2PHP($row);
	    $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($returnArray);
     }
}

//
// dbGetOrdersSummary() - Returns summary data for ALL orders.  The array that is
//			 returned includes rows, each having sumary order data.
//	
//	DATABASE AUDIT - no database changes are made.
//

function dbGetOrdersSummary($sort,$reverse,$all)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     // if $all is true, then show everything, otherwise filter out canceled and shipped orders

     $allSQL = "";
     if(!$all) {
	  $allSQL = "WHERE orders.WasCanceled = 0 AND orders.ShippedDate = 0\n";
     }

     if($reverse) {
	  $reverse = "DESC";
     } else {
	  $reverse = "ASC";
     }

     // this statement was changed to only return the "Countable" items
     // as definied in the packages database.  The goal is to not count
     // the things that aren't ChapRs.
     //
     // The change was made in the sub query, and used to look like:
     //
     //    FROM ( 
     //	        SELECT items.OID, sum(items.Quantity) AS itemCount
     //         FROM items
     //	        GROUP BY items.OID
     //    ) AS subItems
     //
     // Now, as you can see, it includes joining with the packages and
     // only dealing with those packages that are "countable".
     //

     $sql="SELECT customers.CID, 
                  orders.OID,
                  customers.FirstName,
                  customers.LastName,
                  orders.WasCanceled,
                  orders.OrderedDate,
                  orders.ShippedDate,
                  orders.ReleasedToShipping,
                  orders.Charity,
                  orders.PaidDate,
                  orders.IsExpedited,
                  orders.CustomerONotes,
                  orders.AdminONotes,
                  orders.InvoiceNumber,
                  orders.InvoiceID,
                  orders.InvoiceURL,
                  orders.RequestedPay,
                  DATEDIFF(NOW(),orders.RequestedPay) RequestedPayDays,
                  itemCount
                  FROM ( 
                          SELECT items.OID, sum(items.Quantity) AS itemCount
                          FROM items, packages
                          WHERE items.PKID = packages.PKID AND packages.Countable = 1
                          GROUP BY items.OID
                  ) AS subItems
                  INNER JOIN orders ON subItems.OID = orders.OID
                  INNER JOIN customers ON orders.CID = customers.CID
                  $allSQL
                  ORDER BY $sort $reverse, IsExpedited DESC;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetOrdersSummary" . mysqli_error($con));
	  $hadError = true;
     } else {
	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       dbOrderNormalize2PHP($row);
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($returnArray);
     }
}

// 
// dbUpdateInvoice() - update the invoice fields of the given order ($oid)
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//
function dbUpdateInvoice($oid,$requestedDate,$ID,$Number,$URL)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $requestedDate = dbDatePHP2SQL($requestedDate);

     $sql = "UPDATE orders SET RequestedPay='$requestedDate', InvoiceID='$ID', InvoiceNumber='$Number', InvoiceURL='$URL' WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error updating Invoice for order $oid in dbUpdateInvoice() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderDuplicate() - given an $oid, duplicate the order and the items within
//			the order.  The only thing that is NOT duplicated is
//			the customer.
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbOrderDuplicate($oid)
{
     $order = dbGetOrder($oid);
     if(!$order) {
	  return(null);
     }

     // remove the OID from $order, before insertion
     unset($order["OID"]);
     $newoid = dbInsertNewOrder($order["CID"],$order);

     if(!$newoid) {
	  dbOrderDelete($newoid);
	  return(null);
     }

     $items = dbGetItems($oid);
     if($items) {
	  foreach($items as $item) {
	       unset($item["IID"]);
	       $newiid = dbInsertNewItem($newoid,$item);
	       if(!$newiid) {
		    dbOrderDelete($newoid);	// this still leaves orphan items
		    return(null);
	       }
	  }
     }

     return($newoid);
}

//
// dbOrderDelete() - delete the specified order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderDelete($oid)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $sql = "DELETE FROM orders WHERE OID=$oid;";
     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error deleting order $oid in dbOrderDelete() - " . mysqli_error($con));
	  $hadError = true;
     } else {
	  $sql = "SELECT ROW_COUNT();";
	  $result = mysqli_query($con,$sql);
	  if (!$result) {
	       dbErrorMsg("Error getting row count for $oid in dbOrderDelete() - " . mysqli_error($con));
	       $hadError = true;
	  } else {
	       $row = mysqli_fetch_assoc($result);
	       $rows = $row["ROW_COUNT()"];
	       switch($rows) {
	       case 0:
		    dbErrorMsg("OID $oid not found for delete in dbOrderDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       case 1:
		    break;
	       default:
		    dbErrorMsg("Multiple rows for OID $oid in dbOrderDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       }
	       mysqli_free_result($result);
	  }
     }
     dbClose($con);
     return(!$hadError);
}


//
// dbCustomerDelete() - delete the specified customer.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $cid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbCustomerDelete($cid)
{
     if($cid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $sql = "DELETE FROM customers WHERE CID=$cid;";
     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error deleting customer $cid in dbCustomerDelete() - " . mysqli_error($con));
	  $hadError = true;
     } else {
	  $sql = "SELECT ROW_COUNT();";
	  $result = mysqli_query($con,$sql);
	  if (!$result) {
	       dbErrorMsg("Error getting row count for $cid in dbCustomerDelete() - " . mysqli_error($con));
	       $hadError = true;
	  } else {
	       $row = mysqli_fetch_assoc($result);
	       $rows = $row["ROW_COUNT()"];
	       switch($rows) {
	       case 0:
		    dbErrorMsg("CID $cid not found for delete in dbCustomerDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       case 1:
		    break;
	       default:
		    dbErrorMsg("Multiple rows for CID $cid in dbCustomerDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       }
	       mysqli_free_result($result);
	  }
     }
     dbClose($con);
     return(!$hadError);
}

function dbDeleteItem($IID)
{
     if($IID <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $sql = "DELETE FROM items WHERE IID=$IID;";
     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error deleting item $IID in dbItemDelete() - " . mysqli_error($con));
	  $hadError = true;
     } else {
	  $sql = "SELECT ROW_COUNT();";
	  $result = mysqli_query($con,$sql);
	  if (!$result) {
	       dbErrorMsg("Error getting row count for $IID in dbItemDelete() - " . mysqli_error($con));
	       $hadError = true;
	  } else {
	       $row = mysqli_fetch_assoc($result);
	       $rows = $row["ROW_COUNT()"];
	       switch($rows) {
	       case 0:
		    dbErrorMsg("IID $IID not found for delete in dbItemDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       case 1:
		    break;
	       default:
		    dbErrorMsg("Multiple rows for IID $IID in dbItemDelete() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       }
	       mysqli_free_result($result);
	  }
     }
     dbClose($con);
     return(!$hadError);
}

//
// dbOrderAppendAdminONotes() - append the given text to the Admin Order Notes for the given
//				order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
//		Also, all callers with $text must be trusted.

function dbOrderAppendAdminONotes($oid,$text)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     // append the $text to the AdminONotes - but if AdminOnotes is null, concat would
     // fail, so wrap it with the ifnull() function.  ** Sun Nov  2 16:47:54 2014 **
     // NO, WAIT!  Instead, use CONCAT_WS and use "\n" as the separator.

//     $sql = "UPDATE orders SET AdminONotes=CONCAT(ifnull(AdminONotes,''),'$text') WHERE OID=$oid;";
     $sql = "UPDATE orders SET AdminONotes=CONCAT_WS('\n',AdminONotes,'$text') WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error appending text for order $oid in dbOrderAppendAdminONotes() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderDeleteItems() - delete the items for the specified order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderDeleteItems($oid)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $sql = "DELETE FROM items WHERE OID=$oid;";
     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error deleting items for order $oid in dbOrderDeleteItems() - " . mysqli_error($con));
	  $hadError = true;
     } else {
	  $sql = "SELECT ROW_COUNT();";
	  $result = mysqli_query($con,$sql);
	  if (!$result) {
	       dbErrorMsg("Error getting row count for items delete for order $oid in dbOrderDeleteItems() - " . mysqli_error($con));
	       $hadError = true;
	  } else {
	       $row = mysqli_fetch_assoc($result);
	       $rows = $row["ROW_COUNT()"];
	       switch($rows) {
	       case 0:
		    dbErrorMsg("items for order $oid not found for delete in dbOrderDeleteItems() - " . mysqli_error($con));
		    $hadError = true;
		    break;
	       default:
		    break;
	       }
	       mysqli_free_result($result);
	  }
     }
     dbClose($con);
     return(!$hadError);
}

//
// dbOrderModifyCharity() - modify the charity flag for an order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderModifyCharity($oid,$setting)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $setting = dbBoolPHP2SQL($setting);

     // if $cancel is true, the mark record as canceled

     $sql = "UPDATE orders SET Charity='$setting' WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error changing charity flag for order $oid in dbOrderModifyCharity() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderMarkInvoicePaid() - modify the paid date for an order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $invoice could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).  Not as bad as some other
//		routines in that the $invoice and $paiddate are in ticks.
//
function dbOrderMarkInvoicePaid($invoice,$paiddate)
{
     if(!$invoice) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $paiddate = dbDatePHP2SQL($paiddate);

     $sql = "UPDATE orders SET PaidDate='$paiddate' WHERE InvoiceID='$invoice';";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error updating paid status for invoice $invoice in dbOrderMarkInvoicePaid() - " . mysqli_error($con));
	  $hadError = true;
     }

     if(mysqli_affected_rows($con) != 1) {
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderModifyPaid() - modify the paid date for an order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderModifyPaid($oid,$paiddate)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     $paiddate = dbDatePHP2SQL($paiddate);

     // if $cancel is true, the mark record as canceled

     $sql = "UPDATE orders SET PaidDate='$paiddate' WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error changing paid date for order $oid in dbOrderModifyPaid() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderModifyCancel() - modify the canceled flag for an order.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderModifyCancel($oid,$cancel)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     // if $cancel is true, the mark record as canceled

     $sql = "UPDATE orders SET WasCanceled=$cancel WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error marking order $oid in dbOrderModifyCancel() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderCancel() - mark the given order as canceled
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbOrderCancel($oid)
{
     return(dbOrderModifyCancel($oid,dbBoolPHP2SQL(true)));
}

//
// dbOrderUnCancel() - mark the given order as not canceled
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbOrderUnCancel($oid)
{
     return(dbOrderModifyCancel($oid,dbBoolPHP2SQL(false)));
}


//
// dbOrderReleasedToShipping()
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbOrderReleasedToShipping($oid)
{
     return(dbOrderModifyReleasedToShipping($oid,dbDatePHP2SQL(time())));
}

//
// dbOrderUnReleasedToShipping($oid)
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbOrderUnReleasedToShipping($oid)
{
     return(dbOrderModifyReleasedToShipping($oid,dbDatePHP2SQL(0)));
}

//
// dbOrderModifyReleasedToShipping()
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).
//
function dbOrderModifyReleasedToShipping($oid,$sqldate)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(false);
     }

     // just set the date as given

     $sql = "UPDATE orders SET ReleasedToShipping='$sqldate' WHERE OID=$oid;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error setting ReleasedToShipping date for order $oid in dbOrderModifyReleasedToShipping() - " . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);
     return(!$hadError);
}

//
// dbOrderModify() - a general-purpose modification routine that takes an $oid and
//			will change only those fields in the order that are specified
//			in the $modifyFields[].
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).  ALSO, the incoming fields
//		are enclosed in ticks, but still need to be escaped.
//
function dbOrderModify($oid,$modifyFields)
{
     if($oid <= 0) {
	  return(false);
     }

     $hadError = false;

     if(count($modifyFields) > 0) {

	  dbOrderNormalize2SQL($modifyFields);

	  $con = dbConnect();
	  if($con == null) {
	       return(false);
	  }

	  // compose the sql statement for an update...

	  $sql = "UPDATE orders SET ";

	  // using only those fields that are meant to change

	  $first = true;
	  foreach($modifyFields as $modfield => $value) {
	       if(!$first) {
		    $sql .= ", ";
	       }
	       // note that the PHP triple-equal is used here, it
	       // specifies that the $value has to be explicitely null
	       // as opposed to something that "looks" like null - like zero
	       if($value === null) {
		    $sql .= $modfield . "=NULL";
	       } else {
		    $sql .= $modfield . "='$value'";
	       }
	       $first = false;
	  }

	  $sql .= " WHERE OID=$oid;";

	  $result = mysqli_query($con,$sql);
	  if (!$result) {
	       dbErrorMsg("Error setting modifying order $oid in dbOrderModify() - " . mysqli_error($con));
	       $hadError = true;
	  }

	  dbClose($con);
     }

     return(!$hadError);

}


//
// dbGetCustomersSummary() - Returns summary data for ALL customers.  The array that is
//			 returned includes rows, each having sumarry customer data.
//			 The fields in each row array are:
//
//	       CID            - the customer ID for this record
//	       OID            - the order IDs associated with the customer
//             Title          - Mr./Ms./Mrs./Dr.
//	       FirstName      - first name of customer
//	       LastName       - last name of customer
//             City           - city of the address
//             State          - state of the address
//             Country        - country of the address
//             MetDate        - when the customer was encountered (when the file was entered by default)
//             CustomerCNotes - the customer-entered information about the customer
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetCustomersSummary($sort,$reverse)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     if($reverse) {
	  $reverse = "DESC";
     } else {
	  $reverse = "ASC";
     }

#still needs OIDs!!!!!

     $sql="SELECT customers.CID, 
                  customers.Title,
                  customers.FirstName,
                  customers.LastName,
                  customers.City,
                  customers.State,
                  customers.Country,
                  customers.MetDate,
                  customers.CustomerCNotes
            FROM customers
            ORDER BY $sort $reverse;";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetCustomersSummary" . mysqli_error($con));
	  $hadError = true;
     } else {
	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	    dbCustomerNormalize2PHP($row);
	       $row["OID"] = dbGetOrdersForCustomer($row["CID"]);
	       if ($row["State"] == 'ZZ'){
		 $row["State"] = '-';
	       }
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($returnArray);
     }
}

//
// dbGetCustomer() - get the given cutomer data from the database
//		  Returns a simple array.
//		  Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetCustomer($CID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT * FROM customers WHERE customers.CID = $CID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetCustomer" . mysqli_error($con));
	  $hadError = true;
     } else {

	  // only get one row
	  $row = mysqli_fetch_assoc($result);
	  dbCustomerNormalize2PHP($row);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

//
// dbGetShippingInfo() - get the given shipping data from the database
//		  Returns a simple array.
//		  Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetShippingInfo($OID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT ShippedDate, Carrier, TrackingNum, AdminONotes FROM orders WHERE orders.OID = $OID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetShippingInfo" . mysqli_error($con));
	  $hadError = true;
     } else {

	  // only get one row
	  $row = mysqli_fetch_assoc($result);
	  dbShippingNormalize2PHP($row);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

//
// dbGetPackage() - get the given package data from the database
//		  Returns a simple array.
//		  Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetPackage($PKID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT * FROM packages WHERE packages.PKID = $PKID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPackage" . mysqli_error($con));
	  $hadError = true;
     } else {
       // only get one row
       $row = mysqli_fetch_assoc($result);
       dbPackageNormalize2PHP($row);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

function dbGetPiece($PID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT *
           FROM pieces
           WHERE pieces.PID = $PID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetOrder" . mysqli_error($con));
	  $hadError = true;
     } else {
	  // only get one row
	  $row = mysqli_fetch_assoc($result);
	  dbOrderNormalize2PHP($row);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

//
// dbGetOrdersForCustomer() - returns an array of OIDs linked to the given CID; this means
//                            it returns all the orders a customer has placed
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetOrdersForCustomer($CID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT orders.OID
           FROM orders
           WHERE orders.CID = $CID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetOrdersForCustomer" . mysqli_error($con));
	  $hadError = true;
     } else {
       while ($row = mysqli_fetch_assoc($result)){
	 $retVal[] = $row;
       }
       mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($retVal);
     }
}

//
// dbGetOrderCustomer() - get the customer data for the given order from the database
//		          Returns a simple array.
//		          Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetOrderCustomer($OID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT customers.*
           FROM orders,customers
           WHERE orders.OID = $OID AND customers.CID = orders.CID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetOrderCustomer" . mysqli_error($con));
	  $hadError = true;
     } else {

	  // only get one row
	  $row = mysqli_fetch_assoc($result);
	  $row["MetDate"] = dbDateSQL2PHP($row["MetDate"]);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

//
// dbGetOrder() - get the data for the given order from the database
//		  Returns a simple array.
//		  Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetOrder($OID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT * FROM orders WHERE OID = $OID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetOrder" . mysqli_error($con));
	  $hadError = true;
     } else {

	  // only get one row
	  $row = mysqli_fetch_assoc($result);
	  dbOrderNormalize2PHP($row);

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($row);
     }
}

//
// dbGetItems() - get the items for the given order from the database.
//		  Returns an array of items.  Each is an array.
//		  Returns null upon error.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetItems($OID)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT * FROM items WHERE OID = $OID";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetItems" . mysqli_error($con));
	  $hadError = true;
     } else {

	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     if($hadError) {
	  return(null);
     } else {
	  return($returnArray);
     }
}

//
// dbGetPVP() - the the package to piece mapping table and return it.
//		If a PKID (package ID) is given, then only return the
//		list of pieces for that package.  Note that in all
//		cases an array of rows with arrays of (PKID,PID) is
//		returned.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetPVP($PKID = null)
{
     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $where = "";
     if($PKID) {
	  $where = "WHERE PKID=$PKID";
     }

     $sql="SELECT *
           FROM pvp
           $where";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPVP" . mysqli_error($con));
     } else {

	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     return($returnArray);
}

function dbGetPiecesNames()
{
     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT PieceName, PID
            FROM pieces";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPiecesNames" . mysqli_error($con));
     } else {

	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       $returnArray[$row["PID"]] = $row["PieceName"];
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     $returnArray = array_flip($returnArray);

     return($returnArray);
}

//
// dbGetPieces() - get pieces from the database.  If $onlyPersonalities
//		   is true, then only return those.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetPieces($onlyPersonalities = false)
{
     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $where = "";
     if($onlyPersonalities) {
	  $where = "WHERE Active = 1 AND IsPersonality = 1";
     }

     $sql="SELECT PID,
                  PieceName,
                  Abbrev,
                  Active,
                  IsPersonality
           FROM pieces
           $where";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPieces" . mysqli_error($con));
     } else {

	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     return($returnArray);
}

//
// dbGetPersonalities() - returns an array of rows of ONLY personalities
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetPersonalities()
{
     return(dbGetPieces(true));
}


//
// dbGetPackages() - returns an array of rows of packages - the entire packages database
//		     really.  Returns the array or null if it didn't work.
//
//	DATABASE AUDIT - no database changes are made.
//
function dbGetPackages()
{
     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     $sql="SELECT packages.PKID,
                  packages.PackageName,
                  packages.Price,
                  packages.Active
           FROM packages";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql query in dbGetPackages" . mysqli_error($con));
     } else {

	  $returnArray = array();
	  while ($row = mysqli_fetch_assoc($result)) {
	       if($row['Active'] == 0) {
		    $row['Active'] = false;
	       } else {
		    $row['Active'] = true;
	       }
	       $returnArray[] = $row;
	  }

	  mysqli_free_result($result);
     }
     dbClose($con);

     return($returnArray);
}

//
// dbGenericInsert() - used by many insert functions to insert something into a database.
//			Assumes that any field processing is done BEFORE this routine.
//			The incoming array must have all fields (and only those fields)
//			that are valid for that database.  Returns 0 upon failure, or
//			the new key upon success.  All of the fields in the array will
//			be attempted to insert into a new customer record so ONLY USE
//			APPROPRIATE FIELDS for the customer database.
//
//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).  ALSO, the incoming fields
//		are enclosed in ticks, but still need to be escaped.

function dbGenericInsert($row,$dbname,$keyfield,$replace = false)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }

     if(array_key_exists($keyfield,$row)) {
	  $keygiven = $row[$keyfield];
     } else {
	  $keygiven = 0;		// indicates no key given
     }

     if($replace) {
	  $sql = "REPLACE INTO $dbname (";
     } else {
	  $sql = "INSERT INTO $dbname (";
     }

     $first = true;
     foreach($row as $field => $value) {
	  if(!$first) {
	       $sql .= ",";
	  }
	  $sql .= $field;
	  $first = false;
     }
     $sql .= ") VALUES (";
     $first = true;
     foreach($row as $field => $value) {

	  $safeValue = mysqli_real_escape_string($con,$value);

	  if(!$first) {
	       $sql .= ",";
	  }
	  if($value === null) {
	       $sql .= "NULL";
	  } else {
	       $sql .= "'$safeValue'";
	  }
	  $first = false;
     }
     $sql .= ")";

     $result = mysqli_query($con,$sql);
     if (!$result) {
	  dbErrorMsg("Error during sql insert in dbGenericInsert($dbname)" . mysqli_error($con));
	  $hadError = true;
     } else {
	  if($givenCID == 0) {
	       $sql = "SELECT LAST_INSERT_ID() AS $keyfield";
	       $result = mysqli_query($con,$sql);
	       if (!$result) {
		    dbErrorMsg("Error during sql query in dbGenericInsert($dbname)" . mysqli_error($con));
		    $hadError = true;
	       } else {
		    $row = mysqli_fetch_assoc($result);
		    $keygiven = $row[$keyfield];
	       }
	  }
     }

     dbClose($con);

     if(!$hadError) {
	  return($keygiven);
     } else {
	  return(0);
     }
}

//
// dbInsertNewCustomer() - given an array of appropraite values for the customer database,
//			   a new customer record is inserted.  Returns 0 upon failure, or
//			   the new CID upon success.  All of the fields in the array will
//			   be attempted to insert into a new customer record so ONLY USE
//			   APPROPRIATE FIELDS for the customer database.  Some fields,
//			   by-the-way, are massaged to get into the right form for the
//			   database.
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbInsertNewCustomer($row,$replace = false)
{
     // TODO - massage some of the fields to convert from php to sql (like dates)

     $row["MetDate"] = dbDatePHP2SQL($row["MetDate"]);
     return(dbGenericInsert($row,"customers","CID",$replace));
}

function dbInsertNewPackage($row,$replace = false)
{
  dbPackageNormalize2SQL($row);
     return(dbGenericInsert($row,"packages","PKID",$replace));
}
//
// dbInsertNewPVPs() - adds new package-to-piece pairings in the pvp table
//                     by taking in the PKID and the PIDs to be associated with
//                     it in the form of a list of array values (the keys don't matter)
//
function dbInsertNewPVPs($PKID, $PIDs,$replace = false)
{
  foreach ($PIDs as $PID){
    dbGenericInsert(array("PKID" => $PKID, "PID" => $PID),"pvp","PKID",$replace);
  }
}

//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbReplaceCustomer($row)
{
     return(dbInsertNewCustomer($row,true));
}

//
// dbInsertNewOrder($CID,$row) - given an array of appropraite values for the order database
//	       		         a new order record is inserted.  Returns 0 upon failure, or
//			         the new OID upon success.  All of the fields in the array will
//			         be attempted to insert into a new order record so ONLY USE
//			         APPROPRIATE FIELDS for the order database.  Some fields,
//			         by-the-way, are massaged to get into the right form for the
//			         database.
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbInsertNewOrder($CID,$row,$replace = false)
{
     $row["CID"] = $CID;

     dbOrderNormalize2SQL($row);

     return(dbGenericInsert($row,"orders","OID",$replace));
}

//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbReplaceOrder($CID,$row)
{
     return(dbInsertNewOrder($CID,$row,true));
}

//	DATABASE AUDIT - CHANGES ARE MADE - AUDIT NOT COMPLETE
//		The potential issue here is that the $oid could be
//		sent as a non-number by the caller (maybe exposed
//		by custom-crafting a URL).  ALSO, the incoming fields
//		are enclosed in ticks, but still need to be escaped.
//
function dbUpdate($table, $modifyFields, $idName, $idValue, $idName2 = null, $idValue2 = null)
{
     $hadError = false;

     $con = dbConnect();
     if($con == null) {
	  return(null);
     }
     
     if ($idName == "OID"){
       dbOrderNormalize2SQL($modifyFields);
     } else if ($idName == "CID"){
       dbCustomerNormalize2SQL($modifyFields);
     }

     $i = 1;
     $sql = "UPDATE $table SET ";
     foreach ($modifyFields as $column => $value){
       
	  // escape the incoming value to prevent SQL injection

	  $safeValue = mysqli_real_escape_string($con,$value);

	  // note that the PHP triple-equal is used here, it
	  // specifies that the $value has to be explicitely null
	  // as opposed to something that "looks" like null - like zero

       if($value === null) {
	    $sql .= "$column = NULL";
       } else {
	    $sql .= "$column = '$safeValue'";
       }
       if ($i != sizeOf($modifyFields)){
	 $sql .= ", ";
       } else {
	 $sql .= " ";
       }
       $i++;
     }
     $sql .= "WHERE $idName = $idValue ";
     if ($idName2 != null && $idValue2 != null){
       $sql .= "AND $idName2 = $idValue2 ";
     } else {
       $sql .= "LIMIT 1;";
     }

     $result = mysqli_query($con,$sql);

     if (!$result) {
	  dbErrorMsg("Error during sql insert in dbUpdate($dbname)" . mysqli_error($con));
	  $hadError = true;
     }

     dbClose($con);

     if(!$hadError) {
	  return($idValue);
     } else {
	  return(0);
     }

}

function dbIsCountable($PKID)
{
  return dbGetPackage($PKID)['Countable'];
  return false;
}

// dbInsertNewItem($OID,$row) - given an array of appropraite values for the item database
//	       		        a new item record is inserted.  Returns 0 upon failure, or
//			        the new IID upon success.  All of the fields in the array will
//			        be attempted to insert into a new order record so ONLY USE
//			        APPROPRIATE FIELDS for the item database.  Some fields,
//			        by-the-way, are massaged to get into the right form for the
//			        database.
//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbInsertNewItem($OID,$row,$replace = false)
{
     $row["OID"] = $OID;

     return(dbGenericInsert($row,"items","IID",$replace));
}

//
//	DATABASE AUDIT - CHANGES ARE MADE - but at lower levels - AUDIT COMPLETE
//
function dbReplaceItem($OID,$row)
{
     return(dbInsertNewItem($OID,$row,true));
}

//
// dbSettingsLoad() - returns an array with all of the application-wide settings.
//			TODO - right now, it just returns some of our standard
//			static settings.  This needs to be turned into a database
//			table!
//
//	DATABASE AUDIT - no database changes are made.
//
function dbSettingsLoad()
{
     return(array( "ShippingPerChapR" => "10.00",
		   "ExpediteFeeDefault" => "20.00",
		   "CharityKitPKID" => 6,
		   "MaxItems" => 5));
}

?>
