<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Edit Order Custom Page
 *
 * @file           edit-order.php
 * @author         Eric Rothfus
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include('../DataBase/orderList.php');
include('../DataBase/paypalFns.php');
include('../DataBase/wordpress-helper-fns.php');
include_once("../DataBase/settings.php");

//
// trackPackage() - generates a link and text for tracking a package.
//
function trackPackage($order)
{
     $retval = "";

     if(stristr($order["Carrier"],"postal")) {
	  $retval .= "<a href=\"";
	  $retval .= "https://tools.usps.com/go/TrackConfirmAction.action?tRef=fullpage&tLc=1&tLabels=" . $order["TrackingNum"];
	  $retval .= "\">" . $order["TrackingNum"] . "</a>\n";
     } else if(stristr($order["Carrier"],"fedex")) {
	  $retval .= "<a href=\"";
          $retval .= "https://www.fedex.com/fedextrack/WTRK/index.html?action=track&trackingnumber=" .  $order["TrackingNum"];
	  $retval .= "\">" . $order["TrackingNum"] . "</a>\n";
     } else if(stristr($order["Carrier"],"ups")) {
	  $retval .= "<a href=\"";
	  $retval .= "http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=";
	  $retval .= $order["TrackingNum"];
	  $retval .= "\">";
	  $retval .= $order["TrackingNum"];
	  $retval .= "</a>\n";
     } else {
	  $retval .= $order["TrackingNum"];
     }

     return($retval);
}

function showOrderShipping($order,$page_id,$oid,$edit,$showEditButton)
{
     $retval = "";

     $retval .= "<tr>";
     $retval .= "<td>Carrier: <strong>" . $order["Carrier"] . "</strong></td>\n";
     $retval .= "<td>Tracking: <strong>" . trackPackage($order) . "</strong></td>\n";
     $retval .= "</tr>";
     $retval .= "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";

     $retval .= "<tr><td colspan=\"2\" align=\"right\">";
     if($showEditButton) {
	  $retval .= "<a href=\"$edit&OID=$oid\">Edit</a>";
     } else {
	  $retval .= "<em>Payment requested, editing not allowed</em>";
     }
     $retval .= "</td></tr>\n";

     return($retval);
}

function showOrderOrder($order,$page_id,$oid,$edit,$showEditButton)
{
     global $SETTINGS;

     $retval = "";

     $retval .= "<tr><th style=\"text-align:center\">QTY</th>";
     $retval .= "<th>Item</th>";
     $retval .= "<th>Personality</th>";
     $retval .= "<th style=\"text-align:right\">Price</th></tr>";

     $items = dbGetItems($oid);
     $packages = dbGetPackages();
     $pieces = dbGetPieces();

     $total = 0;
     $totalItems = 0;
     foreach($items as $item) {
	  $qty = $item["Quantity"];
	  $retval .= "<tr><td style=\"text-align:center\"><strong>" . $qty . "</strong></td>";
	  $retval .= "<td><strong>" . findPackageName($item["PKID"],$packages) . "</strong></td>";
	  $retval .= "<td><strong>" . findPieceName($item["Personality"],$pieces) . "</strong></td>";
	  $price = findPackagePrice($item["PKID"],$packages) * $qty;
	  $retval .= "<td style=\"text-align:right\"><strong>" . number_format($price,2) . "</strong></td>";
	  $total += $price;
	  $totalItems += $qty;
     }

     $totalTotal = $total;

     $retval .= "</tr>\n";
     $retval .= "<tr class=\"total\"><td></td><td></td>"
	  . "<td style=\"text-align:right;\">ITEMS TOTAL</td>"
	  . "<td style=\"text-align:right\">$ <strong>"
	  . number_format($total,2) . "</strong></td></tr>\n";

     // note that the shipping calculation is very simple here - 
     // and should really be based upon some kind of "weight" or something
     // Note, by-the-way, that this doesn't SET the shipping in the data record
     // that is only "locked in" when the order is invoiced.

     $shipping = $order["ShippingFee"];
     if($shipping === null) {
	  $shipping = $totalItems * $SETTINGS["ShippingPerChapR"];
     }
	  
     $retval .= "<tr><td></td><td></td><td style=\"text-align:right;\">Shipping";
     if($order["ShippingFee"] == null) {
	  $retval .= " (default)";
     }
     $retval .= "</td><td style=\"text-align:right\"><strong>" . number_format($shipping,2) . "</strong></td></tr>\n";

     $totalTotal += $shipping;

     if($order["IsExpedited"]) {
	  // expedite is much like shipping, although it is just a static figure
	  // it is "locked in" when the order is invoiced.

	  $expedite = $order["ExpediteFee"];
	  if($expedite  === null) {
	       $expedite = $SETTINGS["ExpediteFeeDefault"];
	  }
	  
	  $retval .= "<tr><td></td><td></td><td style=\"text-align:right;\">Expedite Fee";
	  if($order["ExpediteFee"] == null) {
	       $retval .= " (default)";
	  }
	  $retval .= "</td><td style=\"text-align:right\"><strong>" . number_format($expedite,2) . "</strong></td></tr>\n";
	  $totalTotal += $expedite;
     }

     if($order["Discount"]) {
	  // discounts only show up if they exist
	  $discount = $order["Discount"];
	  $retval .= "<tr><td></td><td></td><td style=\"text-align:right;\">Discount";
	  $retval .= "</td><td style=\"text-align:right\"><strong>-" . number_format($discount,2) . "</strong></td></tr>\n";
	  $totalTotal -= $discount;
     }	  

     $retval .= "<tr><td></td><td></td><td style=\"text-align:right;\"><strong>TOTAL</strong></td><td style=\"text-align:right\">$ <strong>" . 
	  number_format($totalTotal,2) . "</strong></td></tr>\n";

     $retval .= "<tr><td>&nbsp;</td></tr>";
     $retval .= "<tr><td style=\"vertical-align:top;text-align:right;\">Notes:</td>\n";
     $retval .= "<td colspan=3><strong>" . htmlNotesFormat($order["CustomerONotes"]) . "</strong></td></tr>\n";
     $retval .= "<tr><td style=\"vertical-align:top;text-align:right;\">Admin Notes:</td>\n";
     $retval .= "<td colspan=3><strong>" . htmlNotesFormat($order["AdminONotes"]) . "</strong></td></tr>\n";
     
     $retval .= "<tr><td>&nbsp;</td></tr>";
     $retval .= "<tr><td colspan=\"4\" style=\"text-align:right\">";

     if($showEditButton) {
	  $retval .= "<a href=\"$edit&OID=$oid\">Edit</a>";
     } else {
	  $retval .= "<em>Payment requested, editing not allowed</em>";
     }
     $retval .= "</td></tr>";

     return($retval);

}
function showOrderCustomer($customer,$page_id,$oid,$edit,$showEditButton)
{
     $cid = $customer["CID"];

     $retval = "";
     $retval .= "<tr><td align=\"right\">ID:</td>\n";
     $retval .= "<td><strong>$cid</strong></td></tr>\n";

     $retval .= htmlCustomerAddress($customer);

     $retval .= "<tr><td>&nbsp;</td></tr>";
     $retval .= "<tr><td colspan=\"2\" style=\"text-align:right\">";
     if($showEditButton) {
	  $retval .= "<a href=\"$edit&CID=$cid&OID=$oid\">Edit</a>";
     } else {
	  $retval .= "<em>Payment requested, editing not allowed</em>";
     }
     $retval .= "</td></tr>";
     
     //     print_r($oid);

     return($retval);
}

//
// showOrder() - show the pertinent order information, along with the
//		 buttons to edit it.
//
function showOrder($order,$customer,$page_id,$oid,$editOrder,$editCustomer,$editShipping)
{
     // this USED to stop editing if an order has been invoiced - but we need to
     // be able to edit notes - so we should make it so that only the NUMBERS
     // can't be edited - for now, we just let editing happen

     $canEdit = !$order["RequestedPay"];
     $canEdit = true;

     echo("<table class=\"showorder\"><tr>\n");
     echo("<td width=\"40%\"><table class=\"showordercustomer\">\n");
     echo(showOrderCustomer($customer,$page_id,$oid,$editCustomer,$canEdit));
     echo("</table></td>\n");
     echo("<td>");
     echo("<table class=\"showorderorder\">\n");
     echo(showOrderOrder($order,$page_id,$oid,$editOrder,$canEdit));
     echo("</table>\n");
     if($order["ShippedDate"]) {
	  echo("<table class=\"showordershipping\">\n");
	  echo(showOrderShipping($order,$page_id,$oid,$editShipping,$canEdit));
	  echo("</table>\n");
     }
     echo("</td></tr></table>\n");
}

//
// showPayment() - show the payment section for order editing.
//
function showPayment($order,$page_id,$oid)
{

     echo("<table class=\"tightTable\"><tr><td><table class=\"tightTable\">\n");

     //
     // $lightUpRequestPayment - a boolean that controls whether the buttons for
     //				requesting payment are active, are "lit up"
     //
     $lightUpRequestPayment = current_user_can("can_request_payment")
	  && !$order["Charity"] 
	  && $order["PaidDate"] == 0
	  && !$order["WasCanceled"];

     // requested payment row
     echo("<tr><td style=\"white-space:nowrap\">" . standardIcon("invoice") . "</td><td style=\"white-space:nowrap\">");

     if($order["RequestedPay"]) {
	  echo clickableBox($page_id,$oid,true,"repayment",false,$lightUpRequestPayment);
	  echo " Requested";
	  if($order["InvoiceNumber"] != "" && $order["InvoiceNumber"] > 0) {
	       echo(" - Invoice number: " . $order["InvoiceNumber"] . "<br>");
	  }
	  if($order["InvoiceID"] != "") {
	       echo("PayPal Invoice ID: <a href=\"" . $order["InvoiceURL"] . "\">" . $order["InvoiceID"] . "</a><br>");
	  }
     } else {
	  echo clickableBox($page_id,$oid,false,"payment",false,$lightUpRequestPayment);
	  echo " Payment hasn't yet been requested.";
     }
     echo "</td><td>\n";

     // then do request payment button
     if($order["RequestedPay"]) {
	  echo(prettyButton($page_id,$oid,"Resend Payment Request","repayment",false,$lightUpRequestPayment) . "</td>\n");
	  echo("<td>Request payment from customer again.<br>Can't do if order has already been paid.<br>Or if marked as a Charity ChapR\n</td>");
     } else {
	  echo(prettyButton($page_id,$oid,"Request Payment","payment",false,$lightUpRequestPayment) . "</td>\n");
	  echo("<td>Request payment from customer.<br>Can't do if order has already been paid.<br>Or if marked as a Charity ChapR\n</td>");
     }

     echo("</tr>");

     //
     // $lightUpPaid =  a boolean that controls whether the buttons for
     //				requesting marking paid are active, are "lit up"
     //
     $lightUpPaid = current_user_can("can_mark_paid")
	  && !$order["Charity"] 
	  && !$order["WasCanceled"];

     // row for marking paid
     echo("<tr><td style=\"white-space:nowrap\">" . standardIcon("paid") . "</td><td style=\"white-space:nowrap\">");
     if($order["PaidDate"] != 0) {
	  echo clickableBox($page_id,$oid,true,"unpaid","confirm",$lightUpPaid);
	  echo " Order was paid " . daysCalculator($order["PaidDate"]) . " days ago";
     } else {
	  echo clickableBox($page_id,$oid,false,"paid","confirm",$lightUpPaid);
	  echo  " Order has not been paid.";
     }
     echo("</td><td style=\"white-space:nowrap\">");

     if($order["PaidDate"] != 0) {
	  echo(prettyButton($page_id,$oid,"Mark UN-PAID","unpaid","confirm",$lightUpPaid) . "</td>\n");
	  echo("<td>Override this order as \"UNpaid\".<br>Not normally a good thing to do.\n");
     } else {
	  echo(prettyButton($page_id,$oid,"Mark PAID","paid","confirm",$lightUpPaid) . "</td>\n");
	  echo("<td>Override this order as \"PAID\" <br>(normally this happens in an automated fashion).\n</td>");
     }
     echo("</tr>");
     // end of row for marking paid

     //
     // $lightUpCharity - a boolean that controls whether the buttons for
     //			marking an order as charity are active, are "lit up"
     //

     $lightUpCharity = current_user_can("can_mark_charity")
	  && $order["PaidDate"] == 0
	  && !$order["WasCanceled"];

     // row for charity
     echo("<tr><td style=\"white-space:nowrap\">" . standardIcon("charity") . "</td><td style=\"white-space:nowrap\">");
     if($order["Charity"]) {
	  echo clickableBox($page_id,$oid,true,"uncharity","confirm",$lightUpCharity);
	  echo " Order is a Charity ChapR.";
     } else {
	  echo clickableBox($page_id,$oid,false,"charity","confirm",$lightUpCharity);
	  echo " Standard order (not a Charity ChapR).";
     }
     echo("</td><td style=\"white-space:nowrap\">");
     if($order["Charity"]) {
	  echo(prettyButton($page_id,$oid,"Mark NOT-Charity","uncharity","confirm",$lightUpCharity) . "</td>\n");
	  echo("<td>Override this order as a normal paid order.</td>\n");
     } else {
	  echo(prettyButton($page_id,$oid,"Mark Charity","charity","confirm",$lightUpCharity) . "</td>\n");
	  echo("<td>Override this order as a Charity ChapR\n</td>");
     }
     echo("</tr></table>");
     // end of row for charity

     echo("</table>\n");
}

//
// showShipping() - show the payment section for order editing.  Looks a whole lot like
//			the Payment section in layout and such.
//
function showShipping($order,$page_id,$oid,$edit)
{

     echo("<table class=\"tightTable\"><tr><td><table class=\"tightTable\">\n");

     $lightUpRelease = current_user_can("can_release_to_shipping")
	  && !$order["ShippedDate"]
	  && !$order["WasCanceled"];

     // Release for shipment section - just marks the order as release for shipment

     echo("<tr><td style=\"white-space:nowrap\">" . standardIcon("released") . "</td><td style=\"white-space:nowrap\">");
     if($order["ReleasedToShipping"]) {
	  echo clickableBox($page_id,$oid,true,"unrelease",false,$lightUpRelease);
	  echo " Order was released " . daysCalculator($order["ReleasedToShipping"]) . " days ago";
     } else {
	  echo clickableBox($page_id,$oid,false,"release",false,$lightUpRelease);
	  echo " Order hasn't yet been released to shipping.";
     }
     echo "</td><td>\n";

     // then do release button
     if($order["ReleasedToShipping"]) {
	  echo(prettyButton($page_id,$oid,"UN-Release To Shipping","unrelease",false,$lightUpRelease) . "</td>\n");
	  echo("<td>Un-release this order to shipping.<br>Can't do if order has already been shipped.\n</td>");
     } else {
	  echo(prettyButton($page_id,$oid,"Release To Shipping","release",false,$lightUpRelease) . "</td>\n");
	  echo("<td>Release this order to Shipping.<br>Can't do if order has already been shipped.\n</td>");
     }

     echo("</tr>");

     // partial row for showing the packing list button

     $lightUpShipped = current_user_can("can_mark_shipped")
	  && !$order["WasCanceled"];

     echo("<tr><td></td><td></td><td>");
     echo(prettyButton($page_id,$oid,"Packing List","packing","confirm",$lightUpShipped) . "</td>\n");
     echo("<td>View or print the packing list for shipment.<br>&nbsp;\n</td>");
     echo("</tr>");

     // row for shipping
     echo("<tr><td style=\"white-space:nowrap\">" . standardIcon("shipped") . "</td><td style=\"white-space:nowrap\">");
     if($order["ShippedDate"]) {
	  echo clickableBox($page_id,$oid,true,"shipit",false,$lightUpShipped);
	  echo " Order has been shipped.";
     } else {
	  echo clickableBox($page_id,$oid,false,"unshipit",false,$lightUpShipped);
	  echo " Order has not yet been shipped.";
     }
     echo("</td><td style=\"white-space:nowrap\">");
     if($order["ShippedDate"]) {
	  echo(prettyButton($page_id,$oid,"Mark UN-shipped","unshipit",false,$lightUpShipped) . "</td>\n");
	  echo("<td>Mark this order as NOT being shipped.</td>\n");
     } else {
	  echo(prettyButton($page_id,$oid,"Mark Shipped","shipit",false,$lightUpShipped) . "</td>\n");
	  echo("<td>Mark this order as SHIPPED!<br>You will be prompted for shipment information.\n</td>");
     }
     echo("</tr></table>");
     // end of row for shipping

     echo("</table>\n");
}

//
// orderStatusText() - returns a string with the "standard" status of an
//		       order.
//
function orderStatusText($order)
{
     $retvalue = "";

     $retvalue .= "<table class=\"orderStatusContainer\"><tr><td>";
     $retvalue .= "<table class=\"orderStatus\">\n";
     $retvalue .= htmlOrderStatus($order);
     $retvalue .= "</table>\n";

     if($order["Charity"] || $order["IsExpedited"]) {
	  $retvalue .= "</td><td>";
	  if($order["Charity"]) {
	       $retvalue .= standardIcon("charity");
	  }
	  if($order["IsExpedited"]) {
	       $retvalue .= standardIcon("expedite");
	  }
     }
     $retvalue .= "</td></tr></table>\n";

     return($retvalue);
}

//
// clickRequest() - used by prettyButton() and clickableBox() - execute the given
//		    request when clicked.  If $active is false, the requested button
//		    or image will be shown, but it will be deactivated.
//
function clickRequest($page_id,$oid,$image,$buttonText,$request = null, $confirm = false, $active = true)
{
     $keys = array();
     $keys["page_id"] = $page_id;
     $keys["oid"] = $oid;
     if($request) {
	  $keys["request"] = "$request";
	  if($confirm) {
	       $keys["confirmed"] = "";
	  }
     }

     $retvalue  = "";

     // get rid of the <a> completely if not active

     if($active) {
	  $retvalue .= "<a href=\"?";

	  // this generates an extra "&" at the beginning, but that doesn't hurt anything!
	  // (I don't think...)

	  foreach($keys as $key => $value) {
	       $retvalue .= "&$key=$value";
	  }

	  $retvalue .= "\">";
     }

     if($active) {
	  $isActive = "";
     } else {
	  $isActive = "disabled";
     }

     if($image) {
	  $retvalue .= "$image";
     } else {
	  $retvalue .= "<button type=\"button\" $isActive>$buttonText</button>";
     }

     if($active) {
	  $retvalue .= "</a>\n";
     }

     return($retvalue);
}

//
// clickableBox() - returns an html text string that will paint a un/checked box
//		    that, when clicked, will execute a request - just like prettyButton().
//
function clickableBox($page_id,$oid,$checked,$request = null, $confirm = false, $active = true)
{
     if($checked) {
	  $image = standardIcon("checkedBox");
     } else {
	  $image = standardIcon("box");
     }
     return(clickRequest($page_id,$oid,$image,"",$request,$confirm,$active));
}

//
// prettyButton() - returns a html text string to paint a button.  Configures it
//		    (wordpress style) with the current page id, oid, and other
//		    keys.  The button uses $text for its title.
//
function prettyButton($page_id,$oid,$text,$request = null, $confirm = false, $active = true)
{
     return(clickRequest($page_id,$oid,null,$text,$request,$confirm,$active));
}

//
// orderUnCancel() - "uncanceling" an order is a very specific process for this system.
//		     It means that the old order remains as is in a "canceled" state,
//		     but that a new order is created that looks JUST LIKE that order
//		     except that order processing fields are reset.
//
function orderUnCancel($oid)
{
     // first, duplicate the order itself with a new order number
     //		use the existing customer number

     // next, reset the appropriate fields on the order, and make a note
     //		in the current order that it was based upon a specific
     //		canceled order

     // next, duplicate the items one-by-one in the order, attaching them
     //		to the new order.

     // We're Done!  Except that we return to the NEW order.

     return($newoid);
}

//
// processRequest() - processes any button presses such as cancel
// 		      If $confirmed is true, then go ahead and
//		      do the "thing"
//
//	NOTE - because ANYONE can construct a URL that will cause this routine to be
//		called - this is the last place where permission needs to be checked.
//		Even though it is checked previuosly, we still have to protect against
//		people issuing URLs that didn't flow through our previous checks.
//
function processRequest($order,$page_id,$oid,$request,$deleteLink = "",$packingListLink = "", $shippingLink = "")
{
     switch($request) {
     case "delete":
	  if (current_user_can("can_delete_orders")) {
	       echo("<table><tr><td width=\"50%\">");
	       echo "<h3 style=\"color:red;text-align:center\">WARNING</h3>\n";
	       echo("Deleting orders is BAD!  When you delete an order, all of the\n");
	       echo("history about the order goes away.  It should only be used\n");
	       echo("in extreme circumstances.  Normally, you should do <strong>CANCEL</strong>\n");
	       echo("for orders instead of deleting them.\n");
	       echo("<P>\n");
	       echo("DELETING AN ORDER CANNOT BE UNDONE!");
	       echo("<P>\n");
	       echo("One more thing, for the current version of this system, deleting\n");
	       echo("an order will NOT delete the associated customer record.\n");
	       echo("</td><td>\n");
	       echo(prettyButton($page_id,$oid,"CONFIRM DELETE","delete","confirm"));
	       echo("<P>\n");
	       echo(prettyButton($page_id,$oid,"Go Back"));
	       echo("</td></tr></table>\n");
	  }
	  break;

     case "shipit":
     case "unshipit":
	  if(current_user_can("can_ship")) {
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink,$shippingLink);
	  }
	  break;

     case "uncancel":
	  if(current_user_can("can_cancel_orders")) {
	       echo("<table><tr><td width=\"50%\">");
	       echo("You can UN-cancel this order if you want.  This means");
	       echo(" that this order will be re-instated with all of the data");
	       echo(" that it had before it was canceled - EXCEPT the following:");
	       echo("<UL>");
	       echo("<LI>A new different order number</LI>\n");
	       echo("<LI>The SAME customer information and number</LI>\n");
	       echo("<LI>It will be marked UNPAID</LI>\n");
	       echo("<LI>It will be marked as NOT having sent payment request.</LI>\n");
	       echo("<LI>It will be marked as NOT released to shipping.</LI>\n");
	       echo("<LI>It will be marked as NOT shipped</LI>\n");
	       echo("<LI>A note will be added to Order Notes referencing the old order.</LI>\n");
	       echo("</UL>\n");
	       echo("</td><td>\n");
	       echo(prettyButton($page_id,$oid,"CONFIRM UN-CANCEL","uncancel","confirm"));
	       echo("<P>\n");
	       echo(prettyButton($page_id,$oid,"Go Back"));
	       echo("</td></tr></table>\n");
	  }
	  break;

	  if(current_user_can("can_cancel_orders")) {
	       // this goes straight to uncancel, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case "dup":
	  if(current_user_can("can_duplicate_orders")) {
	       echo("<table><tr><td width=\"50%\">");
	       echo("The most common reason for duplicating an order is that you want ");
	       echo("to ship something else to an existing customer.  This can happen when ");
	       echo("a return/warranty shipment is needed.");
	       echo("<P>\n");
	       echo("Duplicating CAN NOT be \"reversed\", though the duplicate order ");
	       echo("can simply be deleted - leaving no trace. ");
	       echo("<P>\n");
	       echo("<strong>IMPORTANT NOTES:</strong><P><UL><LI>");
	       echo("Duplicating an order only copies the customer information and the ");
	       echo("items for the order.  </LI><LI>Order notes are NOT copied.  </LI><LI>Dates are not copied. ");
	       echo("</LI><LI>Payment information is not copied. </LI><LI>An \"Admin Note\" is added to the ");
	       echo("new copy pointing back to the original order.</LI>");
	       echo("<LI>After you confirm the duplication, you will be transported to the new duplicate order.</LI></UL></P>");
	       echo("</td><td>\n");
	       echo(prettyButton($page_id,$oid,"CONFIRM DUPLICATE","dup","confirm"));
	       echo("<P>\n");
	       echo(prettyButton($page_id,$oid,"Go Back"));
	       echo("</td></tr></table>\n");
	  }
	  break;

     case "cancel":
	  if(current_user_can("can_cancel_orders")) {
	       echo("<table><tr><td width=\"50%\">");
	       echo("Canceling an order is the correct way to say \"this order");
	       echo(" is no longer valid.\"  This can happen when a customer says");
	       echo(" \"no thanks\" when asked for payment, or after awhile when");
	       echo(" a customer doesn't reply.");
	       echo("<P>\n");
	       echo("Canceling CAN be reversed.  However, this means that a new order");
	       echo(" will be created with all of the same information.");
	       echo("<P>\n");
	       echo("Canceling will cause any outstanding invoices with Paypal");
	       echo(" to be canceled too.");
	       echo("<P>\n");
	       echo("Canceling doesn't affect the associated customer record.\n");
	       echo("</td><td>\n");
	       echo(prettyButton($page_id,$oid,"CONFIRM CANCEL","cancel","confirm"));
	       echo("<P>\n");
	       echo(prettyButton($page_id,$oid,"Go Back"));
	       echo("</td></tr></table>\n");
	  }
	  break;

     case "cancelpayment":
	  if(current_user_can("can_cancel_payment")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case "repayment":
	  if(current_user_can("can_request_repayment")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case "payment":
	  if(current_user_can("can_request_payment")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case 'release':
     case 'unrelease':
	  if(current_user_can("can_release_to_shipping")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case 'paid':
     case 'unpaid':
	  if(current_user_can("can_mark_paid")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case 'charity':
     case 'uncharity':
	  if(current_user_can("can_mark_charity")) {
	       // this goes straight to release/unrelease, with no confirm
	       processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  }
	  break;

     case 'packing':
	  processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink,$packingListLink);
	  break;
	  

     default:
	  echo("Dude.  Somehow there was a bad request.\n");
     }
}

//
// lockInFees() - called when the order is "committed" to have a certain
//		  set of fees.  This happens when the customer is sent
//		  a payment request (invoice).  The fees have to be set
//		  in stone at that time.  Granted, they can always be
//		  changed, but shouldn't be after the customer has seen
//		  them.
//
function lockInFees($oid)
{
     global $SETTINGS;

     $changed = false;
     $mod = array();

     $order = dbGetOrder($oid);

     if($order["IsExpedited"] && $order["ExpediteFee"] === null) {
	  $changed = true;
	  $mod["ExpediteFee"] = $SETTINGS["ExpediteFeeDefault"];
     }

     if($order["ShippingFee"] === null) {
	  $changed = true;
	  $items = dbGetItems($oid);

	  // TODO - this isn't a GREAT way to calculate shipping because
	  //        it will also count little things.

	  $count = 0;
	  foreach($items as $item) {
	       $count += $item["Quantity"];
	  }
	  $mod["ShippingFee"] = $count * $SETTINGS["ShippingPerChapR"];
     }

     dbOrderModify($oid,$mod);
}



//
// processRequestConfirmed() - executes the given request
//
function processRequestConfirmed($order,$page_id,$oid,$request,$deleteLink = "",$packingListLink = "",$shippingLink = "")
{
     $message = "";
     $errorMessage = "";

     switch($request) {
     case "delete":
	  if (current_user_can("can_delete_orders")) {
	       dbOrderDelete($oid);
	       dbOrderDeleteItems($oid);
	  }
	  backToLink($deleteLink);
	  return;

     case "shipit":
	  if(current_user_can("can_ship")) {
	       backToLink($shippingLink,"OID=$oid");
	  }
	  break;

     case "unshipit":
	  if(current_user_can("can_ship")) {
	       dbUpdate("orders", array("ShippedDate" => 0), "OID", $oid);
	  }
	  break;

     case "dup":
	  if(current_user_can("can_duplicate_orders")) {
	       $newoid = dbOrderDuplicate($oid);
	       if(!$newoid) {
		    $errorMessage = "Could not duplicate the order!";
	       }
	       // bulk update the new record
	       $updateOrder = array();
	       $updateOrder["OrderedDate"] = time();
	       $updateOrder["WasCanceled"] = false;
	       $updateOrder["WasReceived"] = false;
	       $updateOrder["RequestedPay"] = 0;
	       $updateOrder["InvoiceNumber"] = 0;
	       $updateOrder["InvoiceID"] = "";
	       $updateOrder["InvoiceURL"] = "";
	       $updateOrder["PaidDate"] = 0;
	       $updateOrder["ShippedDate"] = 0;
	       $updateOrder["ShippingFee"] = 0;
	       $updateOrder["ExpediteFee"] = 0;
	       $updateOrder["Discount"] = 0;
	       $updateOrder["ReleasedToShipping"] = 0;

	       // this is somewhat ugly - to put the link for the duplicate order, the text has to have
	       // html in it.  Yuck.

	       $updateOrder["AdminONotes"] = "ORDER DUPLICATE of <a href=\"?page_id=$page_id&oid=$oid\">$oid</a>";
	       $updateOrder["CustomerONotes"] = "";

	       dbOrderModify($newoid,$updateOrder);
	       $message = "Order $oid duplicated to this new order.";
	       $oid = $newoid;
	  }
	  break;

     case "cancel":
	  if(current_user_can("can_cancel_orders")) {
	       dbOrderCancel($oid);
	       $retdata = paypalInvoiceCancel($oid);
	       if($retdata["success"]) {
		    $message = "Order canceled.  Any Paypal invoices canceled.";
	       } else {
		    // upon error, some notice needs to be shown
		    $errorMessage = "Order canceled.  But Paypal rejected the request to cancel the invoice.";
		    $errorMessage .= " It said: ". $retdata["error"][0]["message"];
	       }
	  }
	  break;

     case "uncancel":
	  if(current_user_can("can_cancel_orders")) {
	       $newoid = dbOrderDuplicate($oid);
	       if(!$newoid) {
		    $errorMessage = "Could not duplicate the order!  Order still canceled.";
	       }
	       // bulk update the new record
	       $updateOrder = array();
	       $updateOrder["WasCanceled"] = false;
	       $updateOrder["WasReceived"] = false;
	       $updateOrder["RequestedPay"] = 0;
	       $updateOrder["InvoiceNumber"] = 0;
	       $updateOrder["InvoiceID"] = "";
	       $updateOrder["InvoiceURL"] = "";
	       $updateOrder["PaidDate"] = 0;
	       $updateOrder["ShippedDate"] = 0;
	       $updateOrder["ReleasedToShipping"] = 0;

	       dbOrderModify($newoid,$updateOrder);
	       $message = "Order UN-canceled.  New order number is $newoid.";
	       $oid = $newoid;
	  }
	  break;

     case "cancelpayment":
	  if(current_user_can("can_cancel_payment")) {
	       flush();
	       $retdata = paypalInvoiceCancel($oid);
	       if($retdata) {
		    dbCancelInvoice($oid);
	       } else {
		    // TODO - upon error, soome notice needs to be shown
	       }
	  }
	  break;

     case "repayment":
	  if(current_user_can("can_request_repayment")) {
	       flush();
	       if($order["InvoiceID"]) {
		    $retdata = paypalInvoiceReSend($oid);
	       } else {
		    // apparently the original "invoice" request didn't go through Paypal, so send it there.
		    processRequestConfirmed($order,$page_id,$oid,"payment",$deleteLink,$packingListLink,$shippingLink);
		    return;	// should never get here...
	       }
	       if($retdata["success"]) {
		    $message = "Payment request was resent, which was cool with Paypal.";
	       } else {
		    // upon error, some notice needs to be shown
		    $errorMessage = "Paypal rejected the request to resend the invoice.";
		    $errorMessage .= "<br>It said: ". $retdata["error"][0]["message"];
	       }
	  }
	  break;

     case "payment":
	  if(current_user_can("can_request_payment")) {
	       flush();			// cause the page to send out something to make the user's wait easier...
	       lockInFees($oid);	// this locks in shipping and expedite fees
	       $retdata = paypalInvoiceSend($oid);
	       if($retdata["success"]) {
		    // note that payerViewURL is used instead of invoiceURL because the latter requires login
		    dbUpdateInvoice($oid,time(),$retdata["invoiceID"],$retdata["invoiceNumber"],$retdata["payerViewURL"]);
		    $message = "Payment was requested, Paypal was cool with it..";
	       } else {
		    // upon error, some notice needs to be shown
		    $errorMessage = "Paypal rejected the request to create the invoice.";
		    $errorMessage .= " It said: \"". $retdata["error"][0]["message"] . "\".";
	       }
	  }
	  break;

     case "release":
	  if(current_user_can("can_release_to_shipping")) {
	       dbOrderReleasedToShipping($oid);
	       $message = "Order released to shipping.";
	  }
	  break;

     case "unrelease":
	  if(current_user_can("can_release_to_shipping")) {
	       dbOrderUnReleasedToShipping($oid);
	       $message = "Order un-released to shipping.";
	  }
	  break;

     case 'paid':
	  if(current_user_can("can_mark_paid")) {
	       dbOrderModifyPaid($oid,time());
	       $message = "Order marked paid.";
	  }
	  break;

     case 'unpaid':
	  if(current_user_can("can_mark_paid")) {
	       dbOrderModifyPaid($oid,false);
	       $message = "Order marked un-paid.";
	  }
	  break;

     case 'charity':
	  if(current_user_can("can_mark_charity")) {
	       dbOrderModifyCharity($oid,true);
	       $message = "Order marked as charity.";
	  }
	  break;

     case 'uncharity':
	  if(current_user_can("can_mark_charity")) {
	       dbOrderModifyCharity($oid,false);
	       $message = "Order un-marked as charity.";
	  }
	  break;

     case 'packing':
	  backToLink($packingListLink,"oid=$oid");
	  break;
	  
     default:
	  echo("Dude.  Somehow there was a bad request.\n");
     }

     // go back to the order

     if($message) {
	  $message = "&message=" . urlencode($message);
     }
     if($errorMessage) {
	  $errorMessage = "&errorMessage=" . urlencode($errorMessage);
     }
     backToWPPage($page_id,"oid=$oid$message$errorMessage");
}



//
// This is the landing page when someone clicks on an order from
// the show-orders.php file.  It is sent an OID which it needs to
// allow edit.  If OID isn't set, then it bounces back to the
// previous page.  If the previous page doesn't exist, then it
// goes to the home page.
//

$previousPage = get_home_url();

if(!isset($_GET['ref'])) {
     if(isset($_SERVER['HTTP_REFERER'])) {
	  $previousPage = $_SERVER['HTTP_REFERER'];
     }
}

if (array_key_exists("oid",$_GET)) {
     $oid = $_GET["oid"];
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

// This page can also process order deletes (BAD THING!) and
// canceling of orders.  Both produce appropriate warnings before
// getting confirmation.

$request = "";
if (array_key_exists("request",$_GET)) {
     $request = $_GET["request"];
}

$confirmed = false;
if (array_key_exists("confirmed",$_GET)) {
     $confirmed = true;
}

//
// for user-interface purposes, a message or error can be sent into
// this file - helps a user stay grounded as interesting
// things happen.
$message = "";
if (array_key_exists("message",$_GET)) {
     $message = $_GET["message"];
}
$errorMessage = "";
if (array_key_exists("errorMessage",$_GET)) {
     $errorMessage = $_GET["errorMessage"];
}

// grab all of the different links which link to the editing
// pages for order editing.  They will be blank if not specified,
// which will cause get_page_link() to got to perma-link zero.

$config = getConfigData();

$editOrderLink = getSpecialVariable("editOrderLink",$config);
$shipOrderLink = getSpecialVariable("shipOrderLink",$config);
$editCustomerLink =  getSpecialVariable("editCustomerLink",$config);
$orderListLink =  getSpecialVariable("orderListLink",$config);
$packingListLink =  getSpecialVariable("packingListLink",$config);

//echo "to edit the order part, I'll call " . get_page_link("$editOrderLink"). "<P>";
//echo "to edit the customer part, I'll call " . get_page_link("$editCustomerLink"). "<P>";
//echo "to ship the order part, I'll call " . get_page_link("$shipOrderLink"). "<P>";
//echo "to go back to order list, I'll call " . get_page_link("$orderListLink"). "<P>";


//
// HERE'S WHERE THE ACTUAL GOOD STUFF IS!  Up to this point we have
// be doing a lot of setup.
//

// The plan is to paint a screen describing the order, in a non
// editable format.  Buttons will be available for working with
// the orders.

// first, retrieve the order, which has the side effect of validating it

$order = dbGetOrder($oid);
$customer = dbGetOrderCustomer($oid);

if($message || $errorMessage) {
     echo("<script type='text/javascript'>");
     echo("setTimeout(function() {");
     echo("   var userBox = document.getElementById(\"userMessage\");\n");
     echo("   var errorBox = document.getElementById(\"errorMessage\");\n");

     // we could make the boxes just dissappear like this
     //     echo("   if(userBox) userBox.style.visibility='hidden';\n");
     //     echo("   if(errorBox) errorBox.style.visibility='hidden';\n");

     // instead they actually go away, moving all data up
     echo("   if(userBox) userBox.style.display='none';\n");

     // and we could ALSO cause error messages to go away, but we don't
     //     echo("   if(errorBox) errorBox.style.display='none';\n");

     echo("},5000);\n");
     echo("</script>\n");
}

if($message) {
     echo "<div id=\"userMessage\">" . standardIcon("checkedBox","",false,30,30) . "$message</div>";
}
if($errorMessage) {
     echo "<div id=\"errorMessage\">" . standardIcon("x","",false,30,30) . "$errorMessage</div>";
}

echo "<H3>Order: $oid ";

if($order["IsExpedited"]) {
     echo standardIcon("expedite","",false,50,50);
}

echo "</h3>\n";

echo "<HR>";

if($order['WasCanceled']) {
     echo "CANCELED";
} else {
     echo orderStatusText($order);
}

echo "<HR>";

// before we do anything useful, make sure that user is logged
// in.  Anyone that is logged in can SEE orders...

// check to see if th euesr is logged in - if not, this routine DOES NOT return
checkUserLoggedIn();

// here's where it gets interesting...
//  - if a previous button has been pressed, then we process a bit differently
//  - otherwise, the order is presented

if($request) {
     if($confirmed) {
	  processRequestConfirmed($order,$page_id,$oid,$request,get_page_link($orderListLink),get_page_link($packingListLink),get_page_link($shipOrderLink));
     } else {
	  processRequest($order,$page_id,$oid,$request,get_page_link($orderListLink),get_page_link($packingListLink),get_page_link($shipOrderLink));
     }

} else {

     showOrder($order,$customer,$page_id,$oid,get_page_link($editOrderLink),get_page_link($editCustomerLink),get_page_link($shipOrderLink) . "&edit");

     echo "<HR><H3>Payment</H3>\n";

     showPayment($order,$page_id,$oid);

     echo "<HR><H3> Shipping</H3>\n";

     showShipping($order,$page_id,$oid,get_page_link($shipOrderLink));

     echo "<HR><H3>Overrides</h3>\n";

     echo(prettyButton($page_id,$oid,"DELETE","delete"));
     if($order["WasCanceled"]) {
	  echo(prettyButton($page_id,$oid,"UN-CANCEL","uncancel"));
     } else {
	  echo(prettyButton($page_id,$oid,"CANCEL","cancel"));
     }
     echo(prettyButton($page_id,$oid,"DUPLICATE","dup",false,current_user_can("can_duplicate_orders")));
}

echo("<HR>\n");

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
