<?php

  // packingListPDF.php
  //
  //	Generate the order packing list in PDF format for display/printing
  //	from a browser.  It uses the TCPDF package www.tcpdf.org.
  //	API reference can be found at www.tcpdf.org/doc
  //

include('orderList.php');
include('wordpress-helper-fns.php');
include_once("settings.php");
include_once("../tcpdf/tcpdf.php");

function areaDelta(&$area,$x,$y,$w,$h)
{
     $area = array( 'x' => $area['x'] + $x, 'y' => $area['y'] + $y, 'w' => $area['w'] + $w, 'h' => $area['h'] + $h);
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

//
// pdfSetup() - set-up the pdf page and return the object for working
//		with it.
//
function pdfSetup()
{
     $pdf = new TCPDF("P","in","Letter", true, 'UTF-8', false);

     // set document information
     $pdf->SetCreator("ChapR Order Processing System");
     $pdf->SetAuthor('The ChapR');
     $pdf->SetTitle('Packing List');
     $pdf->SetSubject('Order Packing List');
     $pdf->SetKeywords('ChapR, order');

     // remove default header/footer
     $pdf->setPrintHeader(false);
     $pdf->setPrintFooter(false);

     $pdf->SetAutoPageBreak(true, 0);
     $pdf->SetMargins(0,0,0,0,true);

     $pdf->SetFont('helvetica', '', 14, '', true);
     $pdf->AddPage();

     return($pdf);
}

//
// layout() - calculate the layout of the page, and provide an array
//		of coordinates for use with cells and lines.
//
function layout()
{
     $layout = array();

     // here are the measurements that we're using

     $upperX = .5;
     $upperY = .5;
     $height = 11;
     $width = 8.5;
     $areaWidth = $width - 2*$upperX;
     $areaHeight = $height - 2*$upperY;
     $titleBoxHeight = .3;
     $infoBoxHeight = 3.5;
     $infoBoxWidth = $areaWidth/2;
     $shipInfoHeight = .7;
     $itemsBoxHeight = $areaHeight - $titleBoxHeight - $infoBoxHeight - $shipInfoHeight;
     $dateHeight = .2;


     // now set-up the array

     $layout["area"]     = array( "x" => $upperX, "y" => $upperY,
				  "w" => $areaWidth, "h" => $areaHeight );
     $layout["title"]    = array( "x" => $upperX, "y" => $upperY,
				  "w" => $areaWidth, "h" => $titleBoxHeight );
     $layout["customer"] = array( "x" => $upperX, "y" => $upperY+$titleBoxHeight, 
				  "w" => $infoBoxWidth, "h" => $infoBoxHeight );
     $layout["status"]   = array( "x" => $upperX+$infoBoxWidth, "y" => $upperY+$titleBoxHeight,
				  "w" => $infoBoxWidth, "h" => $infoBoxHeight );
     $layout["items"]    = array( "x" => $upperX, "y" => $upperY+$titleBoxHeight+$infoBoxHeight,
				  "w" => $areaWidth, "h" => $itemsBoxHeight );
     $layout["shipping"] = array( "x" => $upperX, "y" => $upperY+$areaHeight-$shipInfoHeight, 
				  "w" => $areaWidth, "h" => $shipInfoHeight );
     $layout["date"]     = array( "x" => $upperX, "y" => $upperY+$areaHeight,
				  "w" => $areaWidth, "h" => $dateHeight );
     return($layout);
}     

function areaRect($pdf,$area)
{
     return($pdf->Rect($area["x"],$area["y"],$area["w"],$area["h"]));
}

function areaWriteHTMLCell($pdf,$area,$text,$border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopad=false)
{
//     $pdf->setCellPaddings(0,0,0,0;		// these two do nothing I can see
//     $pdf->setCellMargins(0,0,0,0);

     return($pdf->writeHTMLCell(
		 $area["w"],$area["h"],$area["x"],$area["y"],
		 $text,
		 $border, $ln, $fill, $reseth, $align, $autopad));
}


//
// plBoxes() - "packing list boxes" - draw the boxes on the page
//
function plBoxes($pdf,$layout)
{
     // note that fill is blank
     $pdf->SetFillColor(0,0,0);
     $pdf->SetTextColor(128,128,128);
     $pdf->SetLineWidth(0.025);

     areaRect($pdf,$layout["area"]);

     $pdf->SetLineWidth(0.01);
     areaRect($pdf,$layout["title"]);
     areaRect($pdf,$layout["customer"]);
     areaRect($pdf,$layout["status"]);
     areaRect($pdf,$layout["items"]);
     areaRect($pdf,$layout["shipping"]);
}


function titleArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $orderTitle = "<div align=\"center\">";

     if($order["IsExpedited"]) {
	  $orderTitle .= standardIcon("expedite","../Admin") . "&nbsp;&nbsp;";
     }
     if($order["Charity"]) {
	  $orderTitle .= standardIcon("charity","../Admin") . "&nbsp;&nbsp;";
     }

     $orderTitle .= "<strong><em>Order: $oid</em></strong>";

     if($order["Charity"]) {
	  $orderTitle .= "&nbsp;&nbsp;" . standardIcon("charity","../Admin");
     }
     if($order["IsExpedited"]) {
	  $orderTitle .= "&nbsp;&nbsp;" . standardIcon("expedite","../Admin");
     }

     $orderTitle .= "</div>";

     $pdf->setImageScale(1.5);
     $pdf->SetFillColor(0xcc,0xcc,0xcc);
     $pdf->SetTextColor(0,0,0);
     areaWriteHTMLCell($pdf,$area,"$orderTitle",0,0,true);
}

function customerArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $string = "<table><tr><td align=\"center\" colspan=\"2\"><strong>Customer</strong></td></tr>\n";
     $string .= htmlCustomerAddress($customer,"&nbsp;&nbsp;");
     $string .= "</table>";

     $pdf->SetFont('helvetica', '', 8, '', true);
     $pdf->SetFillColor(255,255,255);
     $pdf->SetTextColor(0,0,0);

     // the TCPDF library has a problem with using all of the area for rendering html
     // cutting it quite short - so we temporarily expand the width of the area by
     // an inch...and it seems to work fine - although it does screw-up the centering
     // of the tital of the area.  We really need the extra space though!
     //
     // I tried the following, but instead I just lowered the font size - oh well
     //     areaDelta($area,0,0,1,0);

     areaWriteHTMLCell($pdf,$area,"$string",0,0,true);
}

function statusArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $string = "<table><tr><td align=\"center\" colspan=\"3\"><strong>Status</strong></td></tr>";
     $string .= htmlOrderStatus($order);
     $string .= "<tr><td align=\"center\" colspan=\"3\"><strong>Order Notes</strong></td></tr>";
     $string .= "<tr><td colspan=\"3\">". htmlNotesFormat($order["CustomerONotes"]) . "</td></tr>";
     $string .= "<tr><td colspan=\"3\"><em>". htmlNotesFormat($order["AdminONotes"]) . "</em></td></tr>";
     $string .= "</table>";

     $pdf->SetFont('helvetica', '', 8, '', true);
     $pdf->SetFillColor(255,255,255);
     $pdf->SetTextColor(0,0,0);
     areaWriteHTMLCell($pdf,$area,"$string",0,0,true);
}

function itemsArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $string = "<table width=\"100%\">\n";
     $string .= "<tr><td colspan=\"3\" align=\"center\"><strong>Shipment Items</strong></td></tr>\n";
     $string .= "<tr><td width=\"10%\" style=\"border-bottom:1px solid #888888; text-align:center;\"><strong>QTY</strong></td>";
     $string .= "<td style=\"border-bottom:1px solid #888888;\" width=\"50%\"><strong>Item</strong></td>";
     $string .= "<td style=\"border-bottom:1px solid #888888;\" ><strong>Notes</strong> <em><font size=\"-2\">(include ChapR number(s) if applicable)</font></em></td></tr>\n";

     $string .= "<tr><td>&nbsp;</td></tr>\n";

     foreach($items as $item) {
	  $string .= "<tr>";
	  $string .= "<td align=\"center\"><strong><font size=\"+4\">" . $item["Quantity"] . " x</font></strong></td>";
	  $string .= "<td><strong><font size=\"+1\">" . $item["Name"] . "</font></strong>";
	  $string .= "<UL>";
	  if($item["Personality"]) {
	       $string .= "<LI><font size=\"-1\">" . $item["Personality"] . "</font></LI>";
	  }
	  // now get all of the pieces in the package
	  foreach($mapping as $map) {
	       if($map["PKID"] == $item["PKID"]) {
		    $string .= "<LI><font size=\"-1\">" . findPieceName($map["PID"],$pieces) . "</font></LI>\n";
	       }
	  }
	  $string .= "</UL></td>";
	  $string .= "<td></td>";
	  $string .= "</tr>";
	  $string .= "<tr><td>&nbsp;</td></tr>";	// extra spacing
     }

     $string .= "</table>";

     $pdf->SetFont('helvetica', '', 10, '', true);
     $pdf->SetFillColor(255,255,255);
     $pdf->SetTextColor(0,0,0);
     areaWriteHTMLCell($pdf,$area,"$string",0,0,true);

}

function shippingArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $string = "<table>\n";
     $string .= "<tr><td align=\"center\" colspan=\"7\"><strong>Shipment Details</strong></td></tr>\n";
     $string .= "<tr><td>&nbsp;</td></tr>";	// extra spacing
     $string .= "<tr>";
     $string .= "<td align=\"right\">Date Shipped: </td><td>____________</td>";
     $string .= "<td align=\"right\">Carrier: </td><td>____________</td>";
     $string .= "<td align=\"right\">Tracking #: </td><td colspan=\"2\">_______________________</td>";
     $string .= "</tr>";
     $string .= "</table>";

     $pdf->SetFont('helvetica', '', 10, '', true);
     $pdf->SetFillColor(255,255,255);
     $pdf->SetTextColor(0,0,0);
     areaWriteHTMLCell($pdf,$area,"$string",0,0,true);
}

function dateArea($pdf,$area,$oid,$order,$customer,$items,$mapping,$pieces)
{
     $string = "<div align=\"right\"><font size=\"-3\">";
     $string .= date("F j, Y, g:i a");
     $string .= "</div>";

     $pdf->SetFont('helvetica', '', 8, '', true);
     $pdf->SetFillColor(255,255,255);
     $pdf->SetTextColor(0,0,0);
     areaWriteHTMLCell($pdf,$area,"$string",0,0,true);
}

//
// main() - this function is based upon the "other" packinglist.php (the non
//		PDF version).  But for many reasons, it uses different coding
//		for the different pieces of the packinglist.  Unfortunately,
//		when something changes, it must be recodede in both files.
//		Sorry, future self.

function main($oid)
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

     // set-up the PDF output mechanism

     $pdf = pdfSetup();
     
     // and craft the coordinates of the different areas on the page
     $layout = layout();

     // draw the little boxes around the different areas
     plBoxes($pdf,$layout);

     // the go for all of the data
     titleArea($pdf,$layout["title"],$oid,$order,$customer,$items,$mapping,$pieces);
     customerArea($pdf,$layout["customer"],$oid,$order,$customer,$items,$mapping,$pieces);
     statusArea($pdf,$layout["status"],$oid,$order,$customer,$items,$mapping,$pieces);
     itemsArea($pdf,$layout["items"],$oid,$order,$customer,$items,$mapping,$pieces);
     shippingArea($pdf,$layout["shipping"],$oid,$order,$customer,$items,$mapping,$pieces);
     dateArea($pdf,$layout["date"],$oid,$order,$customer,$items,$mapping,$pieces);

     $pdf->lastPage();
     $pdf->Output('Order-$oid.pdf', 'I');
}

if(array_key_exists("oid",$_GET)) {
     main($_GET["oid"]);
} else {
     echo("<H1>This page must be called with an oid.</h1>\n");
}
?>