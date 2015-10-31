<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Shipping Info Custom Page
 *
 * @file           shipping-edit.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include("../shippingInfoForm.php");
include_once("../DataBase/dbFunctions.php");

function takeShippingFromDataBase($OID)
{
  $dbShipping = dbGetShippingInfo($OID);

  $shipping = array();
  $shipping["OID"] = $OID;
  $shipping["carrier"] = $dbShipping["Carrier"];
  $shipping["trackingNum"] = $dbShipping["TrackingNum"];
  $shipping["shippedDate"] = date("m/d/Y", (($dbShipping["ShippedDate"])?$dbShipping["ShippedDate"]:time()));
  $shipping["adminONotes"] = $dbShipping["AdminONotes"];
  
  return $shipping;
}

function main($OID)
{
  if (!array_key_exists("shippingForm", $_GET)){
    $data = takeShippingFromDataBase($OID);
    showShippingForm($data, "");
  }
  else {
    $badFields = shippingValidate($_GET);
    if (count($badFields) != 0){
      showShippingForm($_GET, "", $badFields);
      print_r($badFields);
    }
    else {
      $OID = addShippingToDataBase($_GET);

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
