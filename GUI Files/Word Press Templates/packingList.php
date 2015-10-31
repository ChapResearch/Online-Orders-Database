<?php

  //
  // packingList.php
  //
  //	Generates an on-screen packing list for an order - suitable
  //	for printing too.
  //

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Packing List Custom Page
 *
 * @file           packingList.php
 * @author         Eric Rothfus
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include('../DataBase/orderList.php');
include('../DataBase/wordpress-helper-fns.php');
include_once("../DataBase/settings.php");

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
     global $SETTINGS;

     $order = dbGetOrder($oid);
     $items = dbGetItems($oid);
     $packages = dbGetPackages();
     $pieces = dbGetPieces();

     $retarray = array();
     foreach($items as $item) {
	  $row = array();
	  $row["PKID"] = $item["PKID"];
	  $row["Quantity"] = $item["Quantity"];
	  $row["Name"] = findPackageName($item["PKID"],$packages);
	  $row["Personality"] = $item["Personality"];
	  if($item["Personality"]) {
	       $row["Personality"] = findPieceName($item["Personality"],$pieces);
	  }
	  $row["Price"] = findPackagePrice($item["PKID"],$packages);
	  $retarray[] = $row;
     }

     // add the charity chapr marketing kit if needed

     if($order["Charity"]) {
	  $row = array();
	  $row["PKID"] = $SETTINGS["CharityKitPKID"];
	  $row["Quantity"] = 1;
	  $row["Name"] = findPackageName($row["PKID"],$packages);
	  $row["Personality"] = null;
	  $retarray[] = $row;
     }

     return($retarray);
}

function main($oid,$pdflink)
{
     // a packing list has all of the standard stuff on it, including
     // the order number, customer, address, but no price information.
     // it combines multiple items into one NUMBERED item along with the
     // stuff that should go into it.  It also includes a field for
     // notes that then need to be transcribed to shipping.

     $order = dbGetOrder($oid);
     $items = getItems($oid);
     $mapping = dbGetPVP();
     $pieces = dbGetPieces();

     if(!$order) {
	  echo("Can't find order $oid\n");
	  return;
     }
     
     $customer = dbGetCustomer($order["CID"]);

     if(!$customer) {
	  echo("Can't find customer for order $oid\n");
	  return;
     }
     
     echo("<div align=\"right\"><font size=\"-2\"><a href=\"$pdflink?oid=$oid\">PDF Version</a></font></div>\n");

     // the whole thing is one big table
     echo("<table class=\"packinglist\">\n");

     // first, the header with "Order: XXX"

     echo("<tr><td class=\"ordertitle\" colspan=\"3\">");
     if($order["IsExpedited"]) {
	  echo(standardIcon("expedite") . "&nbsp;&nbsp;");
     }
     if($order["Charity"]) {
	  echo(standardIcon("charity") . "&nbsp;&nbsp;");
     }

     echo("Order: <strong>$oid</strong>");

     if($order["Charity"]) {
	  echo("&nbsp;&nbsp;" . standardIcon("charity"));
     }
     if($order["IsExpedited"]) {
	  echo("&nbsp;&nbsp;" . standardIcon("expedite"));
     }

     echo("</td></tr>\n");

     // now paint the "To:" and "Status"

     echo("<tr><td width=\"50%\">\n");
     echo("<table><tr><td class=\"title\" colspan=\"2\"><strong>Customer</strong></td></tr>\n");
     echo htmlCustomerAddress($customer);
     echo("</table></td>\n");

     echo("<td width=\"50%\">\n");
     echo("<table><tr><td class=\"title\" colspan=\"3\"><strong>Status</strong></td></tr>\n");
//     echo("<tr><td>");
     echo htmlOrderStatus($order);
//     echo("</td></tr>");
     echo("<tr><td class=\"title\" colspan=\"3\"><strong>Order Notes</strong></td></tr>\n");
     echo("<tr><td colspan=\"3\">". htmlNotesFormat($order["CustomerONotes"]) . "</td></tr>\n");
     echo("<tr><td colspan=\"3\"><em>". htmlNotesFormat($order["AdminONotes"]) . "</em></td></tr>\n");
     echo("</table></td>\n");
     echo("</tr>\n");

     // the middle part of the packing list is the actual list of items and components

     echo("<tr><td colspan=2>\n");
     echo("<table class=\"packingItems\">\n");
     echo("<tr><td class=\"title\" colspan=\"3\"><strong>Shipment Items</strong></td></tr>\n");
     echo("<tr><td style=\"border-bottom:1px solid; text-align:center;\"><strong>Count</strong></td>");
     echo("<td style=\"border-bottom:1px solid;\" width=\"50%\"><strong>Item</strong></td>");
     echo("<td style=\"border-bottom:1px solid;\" ><strong>Notes</strong> <em><font size=-1>(include ChapR number(s) if applicable)</font></em></td</tr>\n");

     echo("<tr><td>&nbsp;</td></tr>\n");

     foreach($items as $item) {
	  echo("<tr>");
	  echo("<td class=\"qty\">" . $item["Quantity"] . " x</td>");
	  echo("<td><p class=\"itemtitle\">" . $item["Name"] . "</p>");
	  echo("<UL>");
	  if($item["Personality"]) {
	       echo("<LI>" . $item["Personality"] . "</LI>");
	  }
	  // now get all of the pieces in the package
	  foreach($mapping as $map) {
	       if($map["PKID"] == $item["PKID"]) {
		    echo("<LI>" . findPieceName($map["PID"],$pieces) . "</LI>\n");
	       }
	  }
	  echo("</UL></td>");
	  echo("<td></td>");
	  echo("</tr>");
     }

     echo("</table></td></tr>\n");

     // the bottom part of the packing list is the shipment note section

     echo("<tr><td colspan=2>\n");
     echo("<table class=\"packingShipping\">\n");
     echo("<tr><td class=\"title\" colspan=\"6\"><strong>Shipment Details</strong></td></tr>\n");
     echo("<tr>");
     echo("<td class=\"fieldName\">Date Shipped: </td><td class=\"field\"></td>\n");
     echo("<td class=\"fieldName\">Carrier: </td><td class=\"field\"></td>\n");
     echo("<td class=\"fieldName\">Tracking #: </td><td class=\"field\"></td>\n");
     echo("</tr>");

     echo("</table></td></tr>\n");

     echo("</table>\n");

}

$config = getConfigData();

$packingListPDFLink = getSpecialVariable("packingListPDFLink",$config);

$oid = null;
if(array_key_exists("oid",$_GET)) {
     $oid = $_GET["oid"];
}
if($oid) {
     main($oid,$packingListPDFLink);
} else {
     echo("<H1>This page must be called with an oid.</h1>\n");
}
?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
