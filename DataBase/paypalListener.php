<?php

  //
  // paypalListener.php
  //
  //	This file serves as the IPN (instant payment notification) listener
  //	for incoming notices from Paypal.  It is used as a direct entry point
  //	from the rest of the world for being notified of anything.  Paypal
  //	has a clever approach:
  //
  //	1 - this listener gets the payment data from the call POSTed to it
  //	2 - it returns a HTTP 200 right away - people who are not paypal
  //		won't see anything interesting
  //	3 - try to "call back" paypal to verify that it WAS a Paypal notification
  //	4 - process the return feedback to verify
  //
  //	The notice is, therefore, only processed if Paypal actually sent it.
  //
  //	The bulk of this code was in a Paypal Developer Site example at:
  //	https://developer.paypal.com/docs/classic/ipn/gs_IPN/
  //

include("paypalFns.php");
include("dbFunctions.php");

function my_process_ipn( $tag,$data,$res ) {
     $out = "";
     $out .= 'Processing IPN Message:\n';
     $out .= var_export( $data,true );
     $out .= "\n\n$res\n\n";

     $myfile = fopen("/tmp/latestIPN-$tag.txt", "w") or die("Unable to open file!");
     fwrite($myfile, $out);
     fclose($myfile);
}

// Start off by sending the empty HTTP 200 OK response 
// to acknowledge receipt of the notification.  Note that
// it actually doesn't have an effect until this script ends.

header('HTTP/1.1 200 OK');

// now extract the data from the POST (for example)

// Build the required acknowledgement message out of the notification just received

my_process_ipn("IN",$_POST,"nada");

$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value) {
     $value = urlencode(stripslashes($value));
     $req  .= "&$key=$value";
}

$ch = curl_init($PAYPAL_IPN_URL);
if ($ch == FALSE) {
     return FALSE;
}

curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

// DEBUGGING - this will put out the curl headers
//
//     curl_setopt($ch, CURLOPT_HEADER, 1);
//     curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

// Set TCP timeout to 30 seconds
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

$res = curl_exec($ch);
if (curl_errno($ch) == 0) { // no cURL error
     if (strcmp ($res, "VERIFIED") == 0) {  // Response contains VERIFIED - process notification
	  my_process_ipn("OK",$_POST,$res);
	  paypalProcessIPN($_POST,true);
     } else {
	  paypalProcessIPN($_POST,false);
     }
}


?>
