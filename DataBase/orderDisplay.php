<?php
  // 
  // orderDisplay.php
  //
  // 	Implements "nicety" functions to make displaying orders easier.
  //

//
// standardIcon() - returns an <img> html string that will reference one of the standard
//                  order icons.  The icons it can handle are in the switch case in this
//                  routine.
//
function standardIcon($iconSelector, $path = "", $hover = true, $height = 25, $width = 25)
{
     switch($iconSelector) {

     case "paid":
	  $hoverText = "The order has been paid for.";
	  $file = "2014/09/paid1.png";
	  break;

     case "charity":
	  $hoverText = "The customer asked for Charity ChapR.";
	  $file = "2014/10/charity.png";
	  break;

     case "shipped":
	  $hoverText  = "The order has been shipped.";
	  $hoverText .= " Note that shipped orders don't appear on order list unless you include them on purpose.";
	  $hoverText .= " See the footer of the table.";
	  $file = "2014/09/truck1.gif";
	  break;

     case "expedite":
	  $hoverText = "The customer asked for expedite service.";
	  $file = "2014/09/Exclamation_Point.png";
	  break;

     case "released":
	  $hoverText = "The order has been released to shipping.";
	  $file = "2014/10/readtoship.png";
	  break;

     case "invoice":
	  $hoverText = "Payment has been requested.";
	  $file = "2014/10/invoice-icon.png";
	  break;

     case "box":
	  $hover = false;		// override hover for the boxes
	  $file = "2014/09/checkbox-unchecked.gif";
	  break;

     case "checkedBox":
	  $hover = false;
	  $file = "2014/09/checkbox-checked.gif";
	  break;

     case "x":
	  $hover = false;
	  $file = "2014/10/Red_X_Icon.png";
	  break;

     default:
	  echo "Error displaying icon in orderDisplay.php\n";
	  break;
     }

     $images = "wp-content/uploads";
     if($path) {
	  $images = "$path/$images";
     }

     $retval = "<img src=\"$images/$file\"";
     if($hover) {
	  $retval .= " title=\"$hoverText\"";
     }
     $retval .= " height=\"$height\" width=\"$width\">"; 

     return($retval);
}

//
// htmlNotesFormat() - format notes fields for display in HTML.  Basically
//			deals with newlines and tabs;
//
function htmlNotesFormat($text)
{
     return(str_replace(array("\n","\t"),array("<br>","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"),$text));
}

//
// daysCalculator() - calculates the number of days between today and the given
//			date/time.  Returns the number of days.
//
function daysCalculator($date)
{	
     return(floor((time() - $date)/60/60/24));
}

//
// htmlCustomerAddress() - composes and returns a string representing the
//			   customer address for the given $fields (which
//			   are expected to be fields from a customer record.
//			   It is assumed that the encompassing table has
//			   already been set-up.  This call will use two
//			   columns.
//
function htmlCustomerAddress($fields,$padding="")
{
     $retval = "";

     $retval .= "<tr><td align=\"right\" width=\"28%\">Name:</td>\n";
     $retval .= "<td><strong>$padding" . $fields["Title"] . " " . $fields["FirstName"] . " " . $fields["LastName"] . "</strong></td></tr>\n";

     $retval .= "<tr><td align=\"right\">Email:</td>\n";
     $retval .= "<td><strong>$padding" . "<a href=\"mailto:" .  $fields["Email"] . "?subject=ChapR\">" . $fields["Email"] . "</a></strong></td></tr>\n";

     $retval .= "<tr><td align=\"right\">Phone:</td>\n";
     $retval .= "<td><strong>$padding" . $fields["Phone"] . "</strong></td></tr>\n";

     $retval .= "<tr><td style=\"vertical-align:top;text-align:right;\">Address:</td>\n";
     $retval .= "<td><strong>$padding" . $fields["Street1"] . "</strong><br>\n";
     if($fields["Street2"] != "") {
	  $retval .= "<strong>$padding" . $fields["Street2"] . "</strong><br>\n";
     }
     $retval .= "<strong>$padding" . $fields["City"] . ", " . $fields["State"] . " " . $fields["Zip"] . "</strong><br>\n";
     $retval .= "<strong>$padding" . $fields["Country"] . "</strong></td></tr>\n";

     $retval .= "<tr><td style=\"vertical-align:top;text-align:right;\">Notes:</td>\n";
     $retval .= "<td><strong>$padding" . htmlNotesFormat($fields["CustomerCNotes"]) . "</strong></td></tr>\n";

     $retval .= "<tr><td style=\"vertical-align:top;text-align:right;\">Admin Notes:</td>\n";
     $retval .= "<td><strong>$padding" . htmlNotesFormat($fields["AdminCNotes"]) . "</strong></td></tr>\n";

     return($retval);
}


//
// htmlOrderStatusLine() - generates a table line for order status
//			based upon given dates.
//
function htmlOrderStatusLine($order,$field,$title,$since = "")
{
     $retvalue = "";

     if($order[$field] != 0) {
	  $retvalue .= "<tr><td>$title</td><td>";
	  if($since) {
	       $retvalue .= date("Y-M-d",$order[$field]);
	  }
	  $retvalue .= "</td><td>";
	  if($since) {
	       $retvalue .= "" . daysCalculator($order[$field]) . " days since $since";
	  }
	  $retvalue .= "</td></tr>\n";
     }

     return($retvalue);
}

//
// htmlOrderStatus() - returns a string with the "standard" status of an
//		       order, where $order has all of the order fields
//
function htmlOrderStatus($order)
{
     $retvalue = "";
     $retvalue .= htmlOrderStatusLine($order,"OrderedDate","Entered","ordered");
     $retvalue .= htmlOrderStatusLine($order,"RequestedPay","Payment Requested","payment requested");
     $retvalue .= htmlOrderStatusLine($order,"PaidDate","Paid","payment received");
     $retvalue .= htmlOrderStatusLine($order,"ReleasedToShipping","Ready to Ship","ready to ship");
     $retvalue .= htmlOrderStatusLine($order,"ShippedDate","Shipped","shipped");

     return($retvalue);
}

?>
