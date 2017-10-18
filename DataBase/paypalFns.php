<?php
  // 
  // paypalFns.php
  //
  // Functions for interfacing with Paypal.  This file isn't meant to
  // be an endpoint, so referencing it as such will do nothing.  Only
  // functions are defined.
  //

  // ** Tue Nov  4 09:28:54 2014 **
  //
  // Requested real credentials.  They are:
  //
  //	Credential	API Signature
  //	API Username	thechapr_api1.gmail.com
  //	API Password	RPCSQMDXPJ33QH67
  //	Signature	A5v9nk3IFbgIGcgozEdPZBdUM.7fAZsqiF03vxOAWv10.Ey5IWYoR9Xc
  //	Request Date	Nov 4, 2014 07:28:18 PST
  //
  // TO GO LIVE -
  //
  //	- flip sandbox below to false
  //	- enable IPN (the URL is already there):  My Account -> Profile -> My Selling Tools
  //	- the doc says I need to register the app - though I can't figure out where
  //	- Figured it out - but *maybe* we can get up and running before doing the app
  //		we'll find out
  //	- had to go through the developers platform, then Applications
  //	- we may need a test account to go through app approval
  //
  // Monday, Sep 14, 2015 - EJR
  //
  //    We received a notice quite some time ago about the fact that PayPal is moving
  //    to higher-level certificates for all of their web sites.  Since we use IPN
  //    (instant payment notification) we have to be concerned with it.
  //
  //    IPN is the thing we use to have the orders database automatically update when
  //    someone pays their PayPal invoice.  Whenever a payment is made, PayPal calls
  //    us back through "paypalListener.php".  That is a non SSL request (because we
  //    don't use certificates ourselves).  BUT THEN, paypalListener.php calls back
  //    PayPal to check that the payment was really received (that someone isn't
  //    just spoofing us).  That call is done from us to them, and our system needs
  //    to be able to speak SSL (https).  Not only does it need to speak SSL, it needs
  //    to do it in the right way.
  //
  //    This is where the change comes in.  PayPal is moving from one "level" of SSL to
  //    another.  Essentially, they are making their systems harder to hack.  When they
  //    move to this level, we need to move with them, or they will simply refuse to talk
  //    to us.  If they won't talk to us, we can't complete our payment validation
  //    process, and won't know when someone pays.  NOTE - we will still get paid!  Our
  //    orders database wouldn't, however, know about it.  So it's not fatal, just really
  //    annoying.
  //
  //    Our side of this change is to make sure that we're speaking "at their level" when
  //    we call them back to verify a payment.  Our programs use the PHP "curl" package
  //    to do this call (its just a web call).  And "curl" is (and always has been) very
  //    smart about secure communications.  So all we have to do is make sure that curl
  //    has access to the right "certificates" to speak at the right level to PayPal.
  //
  //    The short-hand for the level of communication that PayPal is using is:
  //    "the Verisign G5 Public Primary" key.  This is a SHA-256 certificate, which is
  //    256 bits of encryption information and would take millions of years to brute-
  //    force attack.
  //
  //    We need to ensure 3 things:
  //     1 - our OS can support these certificates
  //     2 - our implementation of curl can do this type of certificate
  //     3 - we HAVE this certificate loaded in our bucket of certificates
  //
  //    Number 1 and 2 are easy - "yes."
  //
  //    Number 3 is a bit harder, but running this command line helps:
  //
  //       cat /etc/ssl/certs/ca-certificates.crt | 
  //           awk -v cmd='openssl x509 -noout -subject' '/BEGIN/{close(cmd)};{print | cmd}' |
  //           grep "G5"
  //
  //    (I know I could have just re-directed the file into the "awk" command, but this is
  //    far more readable for this little explanation.)
  //
  //    Running this command on this machine shows what we DO have this certificate installed.
  //    So that means that it should JUST WORK with the new PayPal communication level.
  //    All that's left is to try it in the sandbox, which has already been updated to the
  //    new level.
  //
  //    I'm trying that now...
  //
  //    OK - it works!  Though the sandbox has me a bit worried.  It seems that some of the
  //    fields may be different for the IPN simulator.  At least I'm hoping that's it.
  //    There is a bit of code below that allowed the sandbox to work, which I'm turning
  //    off, so we will know early if things are broken.


include("ponumber.php");

$config = parse_ini_file("/home/rachel/Documents/ordersDB-config.ini");

$sandbox = false;

$PAYPAL_MERCHANT_INFO = array(
	  "name" => "WESTA & Chap Robotics",
	  "phone" => "512-751-9505",
	  "website" => "http://www.TheChapR.com",
	  "line1" => "Norman Morgan - Westlake High School",
	  "line2" => "4100 Westbank Drive",
	  "city" => "Austin",
	  "state" => "TX",
	  "zip" => "78746",
	  "country" => "US",
          "logo" => "https://www.alaracap.com/WESTA_ChapRobotics.png"
);


if($sandbox) {
     $PAYPAL_APP_ID = $config['sandbox_info']['app_id'];
     $PAYPAL_IPN_URL = $config['sandbox_info']['ipn_url'];
     $PAYPAL_SERVICE_HOST = $config['sandbox_info']['service_host'];
     $PAYPAL_SECURITY_USERID = $config['sandbox_info']['security_userid'];
     $PAYPAL_SECURITY_PASSWORD = $config['sandbox_info']['security_password'];
     $PAYPAL_SECURITY_SIGNATURE = $config['sandbox_info']['security_signature'];
     $PAYPAL_MERCHANT_EMAIL = $config['sandbox_info']['merchant_email'];

} else {
     $PAYPAL_APP_ID = $config['live_info']['app_id'];
     $PAYPAL_IPN_URL = $config['live_info']['ipn_url'];
     $PAYPAL_SERVICE_HOST = $config['live_info']['service_host'];
     $PAYPAL_SECURITY_USERID = $config['live_info']['security_userid'];
     $PAYPAL_SECURITY_PASSWORD = $config['live_info']['security_password'];
     $PAYPAL_SECURITY_SIGNATURE = $config['live_info']['security_signature'];
     $PAYPAL_MERCHANT_EMAIL = $config['live_info']['merchant_email'];
}

function paypal_log($tag,$data,$res ) {
     $out = "";
     $out .= 'Processing IPN Message:\n';
     $out .= var_export( $data,true );
     $out .= "\n\n$res\n\n";

     $myfile = fopen("/tmp/latestPaypal-$tag.txt", "w") or die("Unable to open file!");
     fwrite($myfile, $out);
     fclose($myfile);
}

// 
// paypalCommand() - issues a given paypal command.  Returns null if the command
//			failed, or an array of results if it succeeded.
//
function paypalCommand($command,$data)
{
     global $PAYPAL_SERVICE_HOST;
     global $PAYPAL_SECURITY_USERID;
     global $PAYPAL_SECURITY_PASSWORD;
     global $PAYPAL_SECURITY_SIGNATURE;
     global $PAYPAL_MERCHANT_EMAIL;
     global $PAYPAL_MERCHANT_INFO;
     global $PAYPAL_APP_ID;

     $loopCount = 0;     // used to count the service unavailable attempts

     switch($command) {
     case "invoice":
// no longer require the address - we'll get it from the payment message
//	  $requiredFields = array( "FirstName", "LastName", "Email", "Street1", "City", "State", "Zip", "Country" );
	  $requiredFields = array( "FirstName", "LastName", "Email" );
	  break;
     case "invoiceRemind":
     case "invoiceReSend":
     case "invoiceCancel":
	  $requiredFields = array( "InvoiceID" );
	  break;
     }

     foreach($requiredFields as $field) {
	  if(!array_key_exists($field,$data) || $data[$field] == "") {
	       return(null);
	  }
     }

     $defaultingFields = array( "Phone", "Street2" );
     foreach($defaultingFields as $field) {
	  if(!array_key_exists($field,$data)) {
	       $data[$field] = "";
	  }
     }

     // Paypal has very specific country codes - we should use them

     if($data["Country"] == "USA") {
	  $data["Country"] = "US";
     }

     $request = "requestEnvelope.errorLanguage=en_US";

     // 
     // Check for a PO number - this is a hack to make it so that
     // PO numbers can be given by a customer and it can find its
     // way onto the PayPal Invoice.  This was simply the quickest
     // way to get this done.  Sorry. EJR ** Tue Apr 21 10:43:22 2015 **
     // See also ponumber.php in this directory.

     $PONumber = findPONumber($data);
     if($PONumber) {
	  $PONumber = "Your PO Number: " . implode(" ",$PONumber) . "\n";
     }

     switch($command) {
     case "invoice":
	  $commandURL = "https://" . $PAYPAL_SERVICE_HOST . "/Invoice/CreateAndSendInvoice";
	  $request .= "&" . "invoice.merchantEmail=" . urlencode($PAYPAL_MERCHANT_EMAIL);
	  $request .= "&" . "invoice.payerEmail=" . urlencode($data["Email"]);
	  $request .= "&" . "invoice.currencyCode=" . urlencode("USD");
	  $request .= "&" . "invoice.paymentTerms=" . urlencode("DueOnReceipt");
	  $request .= "&" . "invoice.logoUrl=" . urlencode($PAYPAL_MERCHANT_INFO["logo"]);

	  $request .= "&" . "invoice.terms=";
	  if($PONumber) {
	       $request .= urlencode($PONumber);
	  }
	  $request .= urlencode("Order will be shipped upon payment. All payments are donations to WESTA.");

	  $request .= "&" . "invoice.note=" . urlencode("Please contact us if you no longer wish to receive your order: theChapR@gmail.com");
	  $request .= "&" . "invoice.number=" . urlencode($data["OID"]);

	  $request .= "&" . "invoice.shippingAmount=";
	  if($data["ShippingFee"] !== null) {
	       $request .= urlencode($data["ShippingFee"]);
	  } else {
	       $request .= "0";
	  }

	  if($data["IsExpedited"] && $data["ExpediteFee"] !== null) {
	       $request .= "&" . "invoice.customAmountLabel=" . urlencode("Expedite Fee");
	       $request .= "&" . "invoice.customAmountValue=" . urlencode($data["ExpediteFee"]);
	  }

	  if($data["Discount"] != null) {
	       $request .= "&" . "invoice.discountAmount=" . urlencode($data["Discount"]);
	  }

	  $request .= "&" . "invoice.merchantInfo.businessName=" . urlencode($PAYPAL_MERCHANT_INFO["name"]);
	  $request .= "&" . "invoice.merchantInfo.phone=" . urlencode($PAYPAL_MERCHANT_INFO["phone"]);
	  $request .= "&" . "invoice.merchantInfo.website=" . urlencode($PAYPAL_MERCHANT_INFO["website"]);

	  $request .= "&" . "invoice.merchantInfo.address.line1=" . urlencode($PAYPAL_MERCHANT_INFO["line1"]);
	  $request .= "&" . "invoice.merchantInfo.address.line2=" . urlencode($PAYPAL_MERCHANT_INFO["line2"]);
	  $request .= "&" . "invoice.merchantInfo.address.city=" . urlencode($PAYPAL_MERCHANT_INFO["city"]);
	  $request .= "&" . "invoice.merchantInfo.address.state=" . urlencode($PAYPAL_MERCHANT_INFO["state"]);
	  $request .= "&" . "invoice.merchantInfo.address.postalCode=" . urlencode($PAYPAL_MERCHANT_INFO["zip"]);
	  $request .= "&" . "invoice.merchantInfo.address.countryCode=" . urlencode($PAYPAL_MERCHANT_INFO["country"]);

	  // ** Fri Oct 31 09:27:56 2014 **
	  // [EJR] Updated the code to NOT send the address fields if they don't
	  // exist - this SHOULD be OK with Paypal - 'cause the fields are supposedly optional
	  // ** Sun Nov  2 17:02:40 2014 **
	  // Changed this code to consider the shipping/billing info blank if Street1/Street2/City are blank

	  if(trim($data["Street1"] . $data["Street2"] . $data["City"]) != "") {

	       $request .= "&" . "invoice.billingInfo.firstName=" . urlencode($data["FirstName"]);
	       $request .= "&" . "invoice.shippingInfo.firstName=" . urlencode($data["FirstName"]);

	       $request .= "&" . "invoice.billingInfo.lastName=" . urlencode($data["LastName"]);
	       $request .= "&" . "invoice.shippingInfo.lastName=" . urlencode($data["LastName"]);

	       $request .= "&" . "invoice.billingInfo.phone=" . urlencode($data["Phone"]);
	       $request .= "&" . "invoice.shippingInfo.phone=" . urlencode($data["Phone"]);

	       $request .= "&" . "invoice.billingInfo.address.line1=" . urlencode($data["Street1"]);
	       $request .= "&" . "invoice.shippingInfo.address.line1=" . urlencode($data["Street1"]);

	       $request .= "&" . "invoice.billingInfo.address.line2=" . urlencode($data["Street2"]);
	       $request .= "&" . "invoice.shippingInfo.address.line2=" . urlencode($data["Street2"]);

	       $request .= "&" . "invoice.billingInfo.address.city=" . urlencode($data["City"]);
	       $request .= "&" . "invoice.shippingInfo.address.city=" . urlencode($data["City"]);

	       $request .= "&" . "invoice.billingInfo.address.state=" . urlencode($data["State"]);
	       $request .= "&" . "invoice.shippingInfo.address.state=" . urlencode($data["State"]);

	       $request .= "&" . "invoice.billingInfo.address.postalCode=" . urlencode($data["Zip"]);
	       $request .= "&" . "invoice.shippingInfo.address.postalCode=" . urlencode($data["Zip"]);

	       $request .= "&" . "invoice.billingInfo.address.countryCode=" . urlencode($data["Country"]);
	       $request .= "&" . "invoice.shippingInfo.address.countryCode=" . urlencode($data["Country"]);
	  }

	  foreach($data["Items"] as $itemIndex => $item) {
	       $name = $item["Name"];
	       if($item["Personality"] != "") {
		    $name .= " (" . $item["Personality"] . ")";
	       }
	       $request .= "&" . "invoice.itemList.item($itemIndex).name=" . urlencode($name);
	       $request .= "&" . "invoice.itemList.item($itemIndex).quantity=" . urlencode($item["Quantity"]);
	       $request .= "&" . "invoice.itemList.item($itemIndex).unitPrice=" . urlencode($item["Price"]);
	  }
	  break;

     case "invoiceRemind":
	  $commandURL = "https://" . $PAYPAL_SERVICE_HOST . "/Invoice/RemindInvoice";
	  $request .= "&" . "invoiceID=" . urlencode($data["InvoiceID"]);
	  $request .= "&" . "subject=" . urlencode("WESTA & Chap Robotics Invoice Reminder");
	  $request .= "&" . "noteForPayer=" . urlencode(" - Please let us know if you no longer want to receive the ChapR!");
	  break;

     case "invoiceReSend":
	  $commandURL = "https://" . $PAYPAL_SERVICE_HOST . "/Invoice/SendInvoice";
	  $request .= "&" . "invoiceID=" . urlencode($data["InvoiceID"]);
	  break;

     case "invoiceCancel":
	  $commandURL = "https://" . $PAYPAL_SERVICE_HOST . "/Invoice/CancelInvoice";
	  $request .= "&" . "invoiceID=" . urlencode($data["InvoiceID"]);
	  break;

     }

     $headers = array(
	       "User-Agent: ChapR Orders System",
	       "Host: " . $PAYPAL_SERVICE_HOST,
	       "Accept: */*",
	       "X-PAYPAL-SECURITY-USERID: " . $PAYPAL_SECURITY_USERID,
	       "X-PAYPAL-SECURITY-PASSWORD: " . $PAYPAL_SECURITY_PASSWORD,
	       "X-PAYPAL-SECURITY-SIGNATURE: " . $PAYPAL_SECURITY_SIGNATURE,
	       "X-PAYPAL-APPLICATION-ID: " . $PAYPAL_APP_ID,
	       "X-PAYPAL-REQUEST-DATA-FORMAT: NV",
	       "X-PAYPAL-RESPONSE-DATA-FORMAT: JSON",
	       "Content-Type: application/x-www-form-urlencoded");
	        //	  "Content-Length: 403",           - apparently don't need to count length and works

     while(true) {

	  $loopCount += 1;
	  if($loopCount > 5) {			
	       // couldn't get the Paypal service to respond
	       return(null);
	  }

	  $ch = curl_init();

	  curl_setopt($ch,CURLOPT_URL, $commandURL);
	  curl_setopt($ch,CURLOPT_HEADER,false);
	  curl_setopt($ch,CURLOPT_POST,true);
	  curl_setopt($ch,CURLOPT_POSTFIELDS, $request);
	  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);	// return data as a string
	  curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);

	  $resp = curl_exec($ch);
	  if(!$resp) {
	       curl_close($ch);
	       return(null);	// big error - something bad went wrong
	       // die("Error: \"" . curl_error($ch) . "\" - Code: " . curl_errno($ch));
	  }

	  curl_close($ch);

	  if(preg_match("+\<title\>503 Service Temporarily Unavailable\</title\>+",$resp,$matches) == 0) {
	       // NO service unavilable message - so decode return
	       break;
	  }

	  // temporary problem - loop around and try it again
	  sleep(1);
     }

     $respData = json_decode($resp,true);

     paypal_log("action",$respData,"OK");

     return($respData);
}


//
// paypalInvoiceSend() - Sends an order invoice to a customer via paypal.
//			 The customer information is taken from the given
//			 $order.  The order is pulled from the database
//			 directly.  The customer is also pulled form the
//			 database directly.  If for some reason there is
//			 a bad record in the database, where the basic
//			 data is missing, this function returns null.
//			 Otherwise it returns the Paypal response array.
//	RETURNS: NULL or an array of return values from the paypal call,
//		along with $retdata["success"] as either true/false.
//		the important ones are:
//			$retdata["invoiceNumber"]
//			$retdata["invoiceID"]
//			$retdata["invoiceURL"]
//
function paypalInvoiceSend($oid)
{
     $order = dbGetOrder($oid);
     if(!$order) {
	  return(null);
     }

     $customer = dbGetCustomer($order["CID"]);
     if(!$customer) {
	  return(null);
     }

     $items = getItems($oid);

     $alldata = array_merge($order,$customer,array( "Items" => $items));

     $retdata = paypalCommand("invoice",$alldata);

     if($retdata === null) {
	  return(null);
     }

     $retdata["success"] = $retdata["responseEnvelope"]["ack"] == "Success";

     return($retdata);
}

function paypalInvoiceReSend($oid)
{
     $order = dbGetOrder($oid);
     if(!$order) {
	  return(null);
     }

//     $retdata = paypalCommand("invoiceReSend",$order);
     $retdata = paypalCommand("invoiceRemind",$order);

     if($retdata === null) {
	  return(null);
     }

     $retdata["success"] = $retdata["responseEnvelope"]["ack"] == "Success";

     return($retdata);
}

function paypalInvoiceCancel($oid)
{
     $order = dbGetOrder($oid);
     if(!$order) {
	  return(null);
     }

     if(!$order["RequestedPay"]) {
	  $retdata = array();
	  $retdata["success"] = true;
     } else {
	  $retdata = paypalCommand("invoiceCancel",$order);
	  if($retdata === null) {
	       return(null);
	  }
	  $retdata["success"] = $retdata["responseEnvelope"]["ack"] == "Success";
     }

     return($retdata);
}

function asyncDebug($text)
{
     file_put_contents("/tmp/asyncLog.txt", date('Y-m-d H:i:s') . ": $text \n",FILE_APPEND);
}

//
// paypalProcessIPN() - process an incoming Instant Payment Notification from Paypal.
//			There is a listener that always is on the look-out for incoming
//			messages from Paypal.  The only thing that we know how to process
//			though is a payment, and we're pretty carefree about how the
//			payment was made...it just needs to be a payment.  Note that the
//			one thing we're pretty clear about is that a check must be "complete"
//			before we say it's paid.  Here are all of the fields that we can
//			expect, along with the payment types where sent (sorted by level
//			of interest to us).  For a complete list of possible incoming fields, see:
//		 https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/
//
//		FIELD		EXAMPLE DATA				DESCRIPTION
//	-----------------------	-------------------------------	--------------------------------
//	payment_status		Completed			"Completed" or others - note first letter cap
//	payment_type		instant				either "instant" or "echeck"
//	payment_date		17:59:28 Oct 12, 2014 PDT
//	invoice_id		INV2-N5RY-43RH-UU5C-GZHK	This is the invoice ID we use
//
//	BIG NOTE - the IPN url that is given needs to match what the server returns - in other
//	words (and for example) you can't give Paypal a URL with caps in it, because our server
//	thinks that its name is in lower case.
//
function paypalProcessIPN($fields,$goodTransaction)
{
     if($goodTransaction) {
	  // process the good transaction here - or at least it "looks" good for now

       // DEBUGGING CODE - useful...
       //         asyncDebug("here");
       //	  ob_start();
       //	  var_dump($fields);
       //	  $dfields = ob_get_contents();
       //	  ob_end_clean();
       //	  asyncDebug($dfields);

	  if(array_key_exists("payment_status",$fields) && $fields["payment_status"] == "Completed") {

	       $invoice = null;
	       $date = null;
	       if(array_key_exists("invoice_id",$fields)) {
		    $invoice = $fields["invoice_id"];
	       } else {
		 // Sep 14, 2015 - while checking to see if the paypal switch to G5
		 // was going to cause us problems, I found that the sandbox didn't
		 // allow send of invoice_id.  I'm hoping that this is a sandbox
		 // problem only...the following else allows it to work anyway.
		 //		 if(array_key_exists("invoice",$fields)) {
		 //		    $invoice = $fields["invoice"];
		 //		 }
	       }
		    
	       if(array_key_exists("payment_date",$fields)) {
		    $date = strtotime($fields["payment_date"]);
	       }

	       if($date !== null && $invoice !== null) {

		    // now we have a completed payment for a particular invoice
		    // see if we can track it down, and if we can, mark it as paid
		    dbOrderMarkInvoicePaid($invoice,$date);

		    // we'll get the following address fields in:
		    // 'address_street' => may have a \r\n between two lines
		    // 'address_zip' => may be 5/9 chars
		    // 'address_country_code' => 'US',
		    // 'address_name' => 'Eric Rothfus',
		    // 'address_city' => 'AUSTIN',
		    // 'address_state' => 'TX',

		    // Paypal will come back with an address - for the old ChapR data, there is
		    // no address, so we need to slam it into the address.  For the new records
		    // the address may change (someone wants to ship to a different place).  If
		    // that is the case, keep the old address but make a note in the order notes.

		    // so we'll do a comparison, and if it changed, slam in the
		    // new address - making a note about the change

		    $street1 = "";
		    $street2 = "";
		    $streets = explode("\n",str_replace("\r","",$fields["address_street"]));
		    $street1 = $streets[0];
		    if(count($streets) > 1) {
			 $street2 = $streets[1];
		    }
		    $country = $fields["address_country_code"];
		    $state = $fields["address_state"];
		    $zip = $fields["address_zip"];
		    $name = $fields["address_name"];
		    $city = $fields["address_city"];

		    $oid = $fields["invoice_number"];
		  
		    $order = dbGetOrder($oid);
		    $cid = $order["CID"];
		    $customer = dbGetCustomer($cid);

		    // we define no address as an address that is missing a street and city

		    $noAddress = (trim($customer["Street1"] . $customer["Street2"] . $customer["City"]) == "");

		    if($noAddress) {
			 $changes = array();
			 $changes["Street1"] = $street1;
			 $changes["Street2"] = $street2;
			 $changes["City"] = $city;
			 $changes["State"] = $state;
			 $changes["Zip"] = $zip;
			 $changes["Country"] = $country;
			 dbUpdate("customers",$changes,"CID",$cid);
			 $addOrderNote = "Paypal Inserted New Address.";
			 if( strtolower($name) != strtolower($customer["FirstName"] . " " . $customer["LastName"])) {
			      $addOrderNote .= "\n\tName: $name";
			 }
			 dbOrderAppendAdminONotes($oid,$addOrderNote);
		    } else {
			 // here, check to see if it is a different address, make a note if so
			 if( (strtolower($name) != strtolower($customer["FirstName"] . " " . $customer["LastName"])) ||
			     (strtolower($street1) != strtolower($customer["Street1"])) ||
			     (strtolower($street2) != strtolower($customer["Street2"])) ||
			     (strtolower($city) != strtolower($customer["City"])) ||
			     (strtolower($state) != strtolower($customer["State"])) ||
			     (strtolower($country) != strtolower($customer["Country"])) ||
			     (strtolower($zip) != strtolower($customer["Zip"]))) {
			      $addOrderNote = "SHIPPING ADDRESS (from Paypal):\n\t$name\n\t$street1\n";
			      if($street2) {
				   $addOrderNote .= "\t$street2\n";
			      }
			      $addOrderNote .= "\t$city, $state $zip $country\n";
			      dbOrderAppendAdminONotes($oid,$addOrderNote);
			 }
		    }
		    // TODO - need to be VERY CLEAR on our forms that the address
		    //  that the user is supplying is the SHIPPING ADDRESS
	       }
	  }
     }
     // TODO - we silently fail for everything:
     //		- bad transaction
     //		- one with a different status     //		- one where an invoice was paid that we don't know about
}

function findPackageName($PKID,$packages)
{
     foreach($packages as $package) {
	  if($package["PKID"] == $PKID) {
	       return($package["PackageName"]);
	  }
     }
     return("-");
}

function findPackagePrice($PKID,$packages)
{
     foreach($packages as $package) {
	  if($package["PKID"] == $PKID) {
	       return($package["Price"]);
	  }
     }
     return("-");
}

function findPieceName($personality,$pieces)
{
     foreach($pieces as $piece) {
	  if($piece["PID"] == $personality) {
	       return($piece["PieceName"]);
	  }
     }
     return("-");
}

function getItems($oid)
{
     $items = dbGetItems($oid);
     $packages = dbGetPackages();
     $pieces = dbGetPieces();

     $retarray = array();
     foreach($items as $item) {
	  $row = array();
	  $row["Quantity"] = $item["Quantity"];
	  $row["Name"] = findPackageName($item["PKID"],$packages);
	  $row["Personality"] = findPieceName($item["Personality"],$pieces);
	  $row["Price"] = findPackagePrice($item["PKID"],$packages);
	  $retarray[] = $row;
     }

     return($retarray);
}
