<?php
include_once ("htmlFunctions.php");
include_once ("DataBase/dbFunctions.php");
include_once ("DataBase/wordpress-helper-fns.php");
include_once ("DataBase/user.php");

function shippingFields($data, $badFields)
{
  tableRow(array (tableData(prompt("<b>OID*: </b>"), "right"),
		  tableData(prompt($data["OID"]), "left")));

  $carriers = array("UPS" => "1 - UPS", "FedEx" => "2 - FedEx", "US Postal" => "3 - US Postal", "Other" => "4 - Other");

  tableRow(array (tableData(prompt("<b>Carrier*:</b>", in_array("carrier", $badFields)), "right"),
		  tableData(dropDown($data,"carrier", $carriers, "--------Choose The Carrier-------"), "left", "middle")));
  
  tableRow(array (tableData(prompt("<b>Shipping Info (Tracking Num)*:</b>", in_array("trackingNum", $badFields)), "right"),
		  tableData(text($data,"trackingNum"), "left", "middle")));

  tableRow(array (tableData(prompt("<b>Shipped Date*:</b>", in_array("shippedDate", $badFields)), "right"),
		  tableData(text($data,"shippedDate", date("m/d/Y"), "", "datepicker"), "left", "middle")));

  tableRow(array (tableData(prompt("<b>Admin Order Notes:</b>", in_array("adminONotes", $badFields)), "right"),
		  tableData(textArea($data,"adminONotes", 5), "left", "", 5)));

  hiddenField("OID", $data["OID"]);
}

function showShippingForm($data, $action, $badFields = array())
{
  prepDatePicker();

  formHeader($action, "<h1>Shipping Info Form</h1>", "shippingForm", "void");

  shippingFields($data, $badFields);

  tableRow(array (tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(submit("Enter Shipping Details!"),"right")));

  if (inWordPress()){
    echo(getWordpressHiddenFormField());
  }

  formFooter("shippingForm");
}

function shippingValidate($data)
{
  global $repeatTimes;
  $badFields = array();

  if (empty($data["OID"])){
    $badFields[] = "OID";
  }
  if ($data["carrier"] == 0){
    $badFields[] = "carrier";
  }
  if (empty($data["trackingNum"])){
    $badFields[] = "trackingNum";
  }
  if (empty($data["shippedDate"])){
    $badFields[] = "shippedDate";
  }

  return ($badFields);
}

function formatForDataBase($data)
{
  $shipping = array();

  $shipping["OID"] = $data["OID"];
  $shipping["Carrier"] = $data["carrier"];
  $shipping["TrackingNum"] = $data["trackingNum"];
  $shipping["ShippedDate"] = strtotime($data["shippedDate"]);
  $shipping["AdminONotes"] = $data["adminONotes"];

  return $shipping;
}

function addShippingToDataBase($data)
{
  $OID = dbUpdate("orders", formatForDataBase($data), "OID", $data["OID"]);

  return $OID;
}
