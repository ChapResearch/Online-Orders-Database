<?php
include_once ("htmlFunctions.php");
include_once ("DataBase/dbFunctions.php");
include_once("DataBase/wordpress-helper-fns.php");
include_once("DataBase/user.php");
include_once("DataBase/settings.php");

//
// orderFields() - outputs all the html to fill an order form (but does
//                 not include the form definition, hidden field or ending.
//                 It takes in 
function orderFields($data, $badFields)
{
  global $SETTINGS;

  // display data not supposed to be visible for customers
  if (userLoggedIn()){
    if (array_key_exists("OID", $data) && $data["OID"] != ""){
    tableRow(array (tableData(prompt("<b>OID: </b>"), "right", "top"),
		    tableData(prompt($data["OID"]), "left", "top")));
    }
    tableRow(array (tableData(prompt("<b>Expedite:</b>"), "right", "top"),
		    tableData(checkBox($data,"isExpedited","true", "YES"), "left", "middle", 3)));
    prepDatePicker();
    tableRow(array (tableData(prompt("<b>Ordered Date:</b>"), "right", "top"),
		    tableData(text($data,"orderedDate", "", "", "datepicker"), "left", "middle")));
  }  

  // show the order amount change fields if the user has permission (and in WordPress)
  
  if(inWordPress() && current_user_can("can_change_amounts")) {
    tableRow(array( tableData(prompt("<b>Shipping Fee:</b>", in_array("shippingFee", $badFields)),"right"),
		    tableData(text($data,"shippingFee",null,"10"),"left"),
		    tableData(prompt("<b>Expedite Fee:</b>", in_array("expediteFee", $badFields)),"right"),
		    tableData(text($data,"expediteFee",null,"10"),"left"),
		    tableData(prompt("<b>Discount:</b>", in_array("discount", $badFields)),"right"),
		    tableData(text($data,"discount",null,"10"),"left")));
  }

  // figure out how many rows to display initially ($i is set to that value)
  for ($i = $SETTINGS["MaxItems"]; $i > 1; $i--){
    if ((array_key_exists("packages$i", $data) && $data["packages$i"] != "" && $data["packages$i"] != 0)
	|| (array_key_exists("personality$i", $data) && $data["personality$i"] != "" && $data["personality$i"] != 0)
	|| (array_key_exists("quantity$i", $data) && $data["quantity$i"] != "" && $data["quantity$i"] != 0)){
      break;
    }
  }

  $initialRows = $i;

  // get currently available packages (from database) for display
  $rows = dbGetPackages();
  $displayPackages = array();

  foreach ($rows as $row){
    if ($row["Active"]){
      $displayPackages[$row["PackageName"]] = $row["PKID"] ;
    }
  }
  
  // get currently available personalities (from database) for display
  $rows = dbGetPersonalities();
  $displayPersonalities = array();

  foreach ($rows as $row){
    if ($row["Active"]){
      $displayPersonalities[$row["PieceName"]] = $row["PID"] ;
    }
  }

  if (!userLoggedIn()){
    tableRow(array (tableData(prompt("Note: \"personality\" refers to the type of software or platform the firmware is compatible with.
<br> It can be changed later using a USB stick, but we might as well set it for you."), "middle", "top", 6)));
  }

  for ($i = 1; $i <= $SETTINGS["MaxItems"]; $i++){

       // note that the "table-row" setting for display is controversial and may
       // not work well in Microsoft IE

       // note, too, that the reason while rows 2 through 5 don't initially display
       // is that they are set as display = 'none' in the style sheet - if that
       // is turned off, then they will display right away

    $magicClick = "";
    if($i != $SETTINGS["MaxItems"]) {
	 $magicClick = "<button id=\"prodrowclick-";
	 $magicClick .= $i;
	 $magicClick .= "\"";
	 if ($i != $initialRows){
	   $magicClick .= " style=\"visibility:hidden;\"";
	 }
	 $magicClick .= " type=\"button\" onclick=\"";
	 $magicClick .= "document.getElementById('prodrow-";
	 $magicClick .= $i+1; // sets the next row to visible
	 $magicClick .= "').style.display = 'table-row';";
	 if ($i < $SETTINGS["MaxItems"] - 1){
	   $magicClick .= "document.getElementById('prodrowclick-";
	   $magicClick .= $i+1; // sets the next button to visible
	   $magicClick .= "').style.visibility = 'visible';";
	 }
	 $magicClick .= "document.getElementById('prodrowclick-";
	 $magicClick .= $i; // sets its own button to hidden
	 $magicClick .= "').style.visibility = 'hidden';";
	 $magicClick .= "\">+</button>";
    }

    if (userLoggedIn() && array_key_exists("iid$i", $data) && $data["IID"] != ""){
      tableRow(array (tableData(prompt("<b>IID$i:</b>"), "right", "top"),
		      tableData(prompt($data["iid$i"]), "left", "top")));
    }
    
    tableRow(array (tableData(prompt("<b>Product*:</b>", in_array("product$i", $badFields)), "right"),
		    tableData(dropDown($data,"packages$i", $displayPackages, "----------Select Product----------")),
		    tableData(prompt("<b>Personality:</b>", in_array("personality$i", $badFields)), "right"),
		    tableData(dropDown($data,"personality$i", $displayPersonalities, " ")),
		    tableData(prompt("<b>Quantity*:</b>", in_array("quantity$i", $badFields)), "right"),
		    tableData(text($data,"quantity$i", "", "2"),"left"),
		    tableData($magicClick)),"prodrow-" . $i, $i <= $initialRows);
    
    hiddenField("iid$i", $data["iid$i"]);
  }    

  if (!userLoggedIn()){
    tableRow(array (tableData(prompt("Write anything you would like us to know about the order: <br> a deadline you need to meet, some option you want that isn't offered etc."), "middle", "top", 6)));
  }

  tableRow(array (tableData(prompt("<b>Order Notes:</b>"), "right", "top"),
		    tableData(textArea($data,"customerONotes", 5), "left", "", 5)));
   
  if (userLoggedIn()){
    tableRow(array (tableData(prompt("<b>Admin Order Notes:</b>"), "right", "top"),
		    tableData(textArea($data,"adminONotes", 5), "left", "", 5)));
  }
  hiddenField("charity", $data["charity"]);
  hiddenField("OID", $data["OID"]);
}

//
// showOrderForm() - displays the form (using a table), containing the order fields
//                   (which are painted by the function orderFields(), including the
//                   hidden fields). The function then adds the WordPress field, which
//                   indicates the page id the form is on. Finally the submit button
//                   and formFooter() close off the table and form.
function showOrderForm($data, $action, $badFields = array())
{
  global $SETTINGS;

  formHeader($action, "Order Info Form", "orderForm", "void");
  
  orderFields($data, $badFields);

  echo getWordpressHiddenFormField();
  
  tableRow(array (tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(submit("Enter Order!"),"right")));

  formFooter("orderForm");
}

//
// orderValidate() - make sure the order fields are appropriate.
//			If a field is not, put it into an array
//			that indicates the bad fields.
//
function orderValidate($data)
{
  global $SETTINGS;

  $badFields = array();

  if ($data["shippingFee"] != "" && !is_numeric($data["shippingFee"])){
    $badFields[] = "shippingFee";
  }

  if ($data["expediteFee"] != "" && !is_numeric($data["expediteFee"])){
    $badFields[] = "expediteFee";
  }

  if ($data["discount"] != "" && !is_numeric($data["discount"])){
    $badFields[] = "discount";
  }


  $count = 0;
  for ($i = 1; $i <= $SETTINGS["MaxItems"]; $i++){
    if ($data["packages$i"] != 0 && ($data["quantity$i"] == "" || !is_numeric($data["quantity$i"]))){
      $badFields[] = "quantity$i";
    }
    if (empty($data["packages$i"]) && $data["quantity$i"] != 0){
      $badFields[] = "product$i";
    }
    else {
      $count += $data["quantity$i"];
    }
  }
  if ($count == 0){
    $badFields[] = "product1";
    $badFields[] = "quantity1";
  }
  
  return ($badFields);
}

//
// translateOrderInfo() - transfer order information from the given array to be in the database's
//                        format (normally from the $_GET). Some values are defaulted to 0, some 
//                        are defaulted based on the $SETTINGS, and others are not included if the
//                        data array doesn't contain that key.
//
function translateOrderInfo($data)
{
  global $SETTINGS;
  $order = array();

  if (array_key_exists("OID", $data)){
    $order["OID"] = $data["OID"];
  } else if (array_key_exists("oid", $data)){ // protects against different capitalization
    $order["OID"] = $data["oid"];
  }

  if (array_key_exists("orderedDate", $data) && $data["orderedDate"] != ""){
    $order["OrderedDate"] = strtotime($data["orderedDate"]);
  } else {
    $order["OrderedDate"] = time();
  }
  $order["CustomerONotes"] = $data["customerONotes"];
  $order["AdminONotes"] = $data["adminONotes"];
  $order["IsExpedited"] = $data["isExpedited"];
  $order["RequestedPay"] = 0;
  if (array_key_exists("charity", $data)){
    $order["Charity"] = $data["charity"];
  }
  $order["PaidDate"] = 0;
  $order["ShippedDate"] = 0;
  $order["ReleasedToShipping"] = 0;
  $order["Carrier"] = "";
  $order["TrackingNum"] = 0;
  $order["WasReceived"] = 0;
  $order["WasCanceled"] = 0;

  // the fees here are either set, or if blank, then they are set to null
  $order["ExpediteFee"] = $data["expediteFee"];
  if ($data["expediteFee"] == ""){
       $order["ExpediteFee"] = NULL;
  }

  $order["ShippingFee"] = $data["shippingFee"];
  if ($data["shippingFee"] == ""){
       $order["ShippingFee"] = NULL;
  }

  $order["Discount"] = $data["discount"];
  if ($data["discount"] == ""){
    $order["Discount"] = NULL;
  }

  return $order;
}

//
// addOrderToDataBase() - insert the new order given the order info (translated above by
//                        translateOrderInfo(), then add the new items, looping through
//                        the $data array for however many items max possible, given by the
//                        $SETTINGS array (no translation needed).
//
function addOrderToDataBase($CID, $data)
{
  global $SETTINGS;

  $OID = dbInsertNewOrder($CID,translateOrderInfo($data));

  $item = array();
  for($i = 1; $i <= $SETTINGS["MaxItems"]; $i++){
    $item["PKID"] = $data["packages$i"];
    $item["Personality"] = $data["personality$i"];
    $item["Quantity"] = $data["quantity$i"];
    if ($item["Quantity"] != 0){
      $IID = dbInsertNewItem($OID, $item);
    }
  }
  return ($OID);
}

function orderConfirmation($OID)
{
  if (inWordPress()){
    echo "<script> window.location.href = \"?page_id=130&oid=$OID\"; </script>";
  } else {
    echo "<P align=\"center\">\n";
    echo '<table border="1" width=50%><tr><td align="center">';
    echo '<b>Your order has been submitted!</b><br>';
    echo "<input type=\"button\" value=\"Back\" onclick=\"window.top.location.href='http://www.thechapr.com';\"><P>";
    echo '<em>Be careful if you use the back button in your browser, your form will re-appear and you may enter the order twice!</em>';
    echo "</tr></td></table>\n";
  }
}

?>