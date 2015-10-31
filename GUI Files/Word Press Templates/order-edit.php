<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Order Edit Custom Page
 *
 * @file           order-edit.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include("../orderForm.php");

$repeatTimes = 5;

function takeOrderFromDataBase($OID)
{
  $data = array();

  $data["OID"] = $OID;

  $order = dbGetOrder($OID);

  $data["customerONotes"] = $order["CustomerONotes"];
  $data["adminONotes"] = $order["AdminONotes"];
  $data["isExpedited"] = $order["IsExpedited"];
  $data["orderedDate"] = date("m/d/Y", $order["OrderedDate"]);

  // note that ShippingFee, ExpediteFee, and Discount are null
  // if not specified - to distinquish them from being 0.00
  // translate the null to a blank string here, then back to
  // null on the way out of the form.

  $data["shippingFee"] = $order["ShippingFee"];
  if($data["shippingFee"] === null) {
       $data["shippingFee"] = "";
  }
  $data["expediteFee"] = $order["ExpediteFee"];
  if($data["expediteFee"] === null) {
       $data["expediteFee"] = "";
  }
  $data["discount"] = $order["Discount"];
  if($data["discount"] === null) {
       $data["discount"] = "";
  }

  $items = dbGetItems($OID);

  $i = 1;
  foreach ($items as $item){
    $data["iid$i"] = $item["IID"];
    $data["personality$i"] = $item["Personality"];
    $data["packages$i"] = $item["PKID"];
    $data["quantity$i"] = $item["Quantity"];
    $i++;
  }

  return $data;
}

// selectItem() - returns the item information as taken from the $data array
//               returns an empty array if the quantity of the item is not set
//
function selectItem($data, $i)
{
    $retVal["PKID"] = $data["packages$i"];
    $retVal["Personality"] = $data["personality$i"];
    if ($data["quantity$i"] == "") {
      return array();
    } else {
      $retVal["Quantity"] = $data["quantity$i"];
    }

  return $retVal;
}

function formatNonItemFields($data)
{
  $retArray["IsExpedited"] = $data["isExpedited"];
  $retArray["CustomerONotes"] = $data["customerONotes"];
  $retArray["AdminONotes"] = $data["adminONotes"];
  $retArray["OrderedDate"] = strtotime($data["orderedDate"]);

  // if ShippingFee, ExpediteFee, or Discount are
  // empty strings, then set them back to null
  // For these fields "null" means unspecified so
  // the defaults can take over.

  $retArray["ShippingFee"] = $data["shippingFee"];
  if($retArray["ShippingFee"] == "") {
       $retArray["ShippingFee"] = null;
  }

  $retArray["ExpediteFee"] = $data["expediteFee"];
  if($retArray["ExpediteFee"] == "") {
       $retArray["ExpediteFee"] = null;
  }

  $retArray["Discount"] = $data["discount"];
  if($retArray["Discount"] == "") {
       $retArray["Discount"] = null;
  }

  return $retArray;
}

function main($OID)
{
  global $repeatTimes;

  if (!array_key_exists("orderForm", $_GET)){
    $data = takeOrderFromDataBase($OID);
    showOrderForm($data, "");
  }
  else {
    $badFields = orderValidate($_GET);
    if (count($badFields) != 0){
      showOrderForm($_GET, "", $badFields);
    }
    else {
      dbUpdate("orders", formatNonItemFields($_GET), "OID", $OID);
      for ($i = 1; $i < $repeatTimes; $i++){
	$item = selectItem($_GET, $i);
	if (!empty($item)){ // checks to be sure the item actually has info
	  if ($item["Quantity"] != 0){
	    if ($_GET["iid$i"] != ""){
	      dbUpdate("items", $item, "OID", $OID, "IID", $_GET["iid$i"]);
	    } else { // if the item does not yet exist in the database
	      dbInsertNewItem($OID, $item);
	    }
	  }
	  else { // if the quantity of an item is set to zero
	    dbDeleteItem($_GET["iid$i"]);
	  }
	}
      }

      // go back to the order page

      $config = getConfigData();	
      $backToOrder =  getSpecialVariable("backToOrder",$config);
      backToWPPage($backToOrder,"oid=$OID");
    }
  }
}

main($_GET["OID"]);
?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
