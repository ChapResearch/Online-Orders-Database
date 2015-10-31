<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Edit Customer Custom Landing Page
 *
 * @file           edit-customer.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include('../DataBase/customerList.php');
include('../DataBase/wordpress-helper-fns.php');

//
// prettyButton() - returns a html text string to paint a button.  Configures it
//		    (wordpress style) with the current page id, cid, and other
//		    keys.  The button uses $text for its title.
//
function prettyButton($page_id,$cid,$text,$request = null, $confirm = false)
{
     $keys = array();
     $keys["page_id"] = $page_id;
     $keys["CID"] = $cid;
     if($request) {
	  $keys["request"] = "$request";
	  if($confirm) {
	       $keys["confirmed"] = "";
	  }
     }

     $retvalue  = "";
     $retvalue .= "<a href=\"?";

     // this generates an extra "&" at the beginning, but that doesn't hurt anything!
     // (I don't think...)

     foreach($keys as $key => $value) {
	  $retvalue .= "&$key=$value";
     }

     $retvalue .= "\"><button type=\"button\">$text</button></a>\n";

     return($retvalue);
}

//
// processRequest() - processes any button presses such as cancel
// 		      If $confirmed is true, then go ahead and
//		      do the "thing"
//
function processRequest($page_id,$cid,$request)
{
  global $editCustomerLink;

     switch($request) {
     case "delete":
	  echo("<table><tr><td width=\"50%\">");
	  echo "<h3 style=\"color:red;text-align:center\">WARNING</h3>\n";
	  echo("Deleting customers is BAD!  When you delete an customer, all of the\n");
	  echo("history about the customer goes away.  It should only be used\n");
	  echo("in extreme circumstances.  Normally, you should do <strong>CANCEL</strong>\n");
	  echo("for customers instead of deleting them.\n");
	  echo("<P>\n");
	  echo("DELETING AN CUSTOMER CANNOT BE UNDONE!");
	  echo("<P>\n");
	  echo("One more thing, for the current version of this system, deleting\n");
	  echo("an customer will also delete the associated customer record.\n");
	  echo("</td><td>\n");
	  echo(prettyButton($page_id,$cid,"CONFIRM DELETE","delete","confirm"));
	  echo("<P>\n");
	  echo(prettyButton($page_id,$cid,"Go Back"));
	  echo("</td></tr></table>\n");
	  break;
     case "edit":
       $magicLink = get_page_link($editCustomerLink) . "&CID=$cid";
       echo ("<script> window.location.href = \"$magicLink\"; </script>");
       break;
      default:
	  echo("Dude.  Somehow there was a bad request.\n");
     }
}

//
// processRequestConfirmed() - executes the given request
//
function processRequestConfirmed($page_id,$cid,$request)
{
     switch($request) {
     case "delete":
	  dbCustomerDelete($cid);
	  foreach (dbGetOrdersForCustomer($cid) as $stuff){
	    foreach($stuff as $order){
	      print_r(dbGetOrdersForCustomer($cid));
	      dbOrderDelete($order["OID"]); 
	    }
	  }
	  break;
     default:
	  echo("Dude.  Somehow there was a bad request.\n");
     }
}

//
// This is the landing page when someone clicks on a customer from
// the show-customers.php file.  It is sent an CID which it needs to
// allow edit.  If CID isn't set, then it bounces back to the
// previous page.  If the previous page doesn't exist, then it
// goes to the home page.
//

$previousPage = get_home_url();

if(!isset($_GET['ref'])) {
     if(isset($_SERVER['HTTP_REFERER'])) {
	  $previousPage = $_SERVER['HTTP_REFERER'];
     }
}

if (array_key_exists("CID",$_GET)) {
     $cid = $_GET["CID"];
} else {
     echo "<script> window.location.href = \"$previousPage\"; </script>";
}

//
// since we are in wordpress, go ahead and get our page_id so we can
// compose appropriate URLs for processing
//
$page_id = "";
if (array_key_exists("page_id",$_GET)) {
     $page_id = $_GET["page_id"];
}

// This page can also process customer deletes (BAD THING!) and
// canceling of customers.  Both produce appropriate warnings before
// getting confirmation.

$request = "";
if (array_key_exists("request",$_GET)) {
     $request = $_GET["request"];
}

$confirmed = false;
if (array_key_exists("confirmed",$_GET)) {
     $confirmed = true;
}

// grab all of the different links which link to the editing
// pages for customer editing.  They will be blank if not specified,
// which will cause get_page_link() to got to perma-link zero.

$config = getConfigData();

$editCustomerLink = getSpecialVariable("editCustomerLink",$config);
$deleteCustomerLink = getSpecialVariable("deleteCustomerLink",$config);
$releaseCustomerLink = getSpecialVariable("releaseCustomerLink",$config);

//
// HERE'S WHERE THE ACTUAL GOOD STUFF IS!  Up to this point we have
// be doing a lot of setup.
//

// The plan is to paint a screen describing the customer, in a non
// editable format.  Buttons will be available for working with
// the customers.

// first, retrieve the customer, which has the side effect of validating it

$customer = dbGetCustomer($cid);

echo "<H1>Customer: $cid</h1>\n";

echo "<HR>";

// here's where it gets interesting...
//  - if a previous button has been pressed, then we process a bit differently
//  - otherwise, the customer is presented

if($request) {
     if($confirmed) {
	  processRequestConfirmed($page_id,$cid,$request);
     } else {
	  processRequest($page_id,$cid,$request);
     }

} else {
     echo(prettyButton($page_id,$cid,"DELETE","delete"));
     echo(prettyButton($page_id,$cid,"EDIT","edit"));
}

echo("<HR>\n");

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
