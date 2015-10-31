<?php

  // orderEntry.php
  // 
  // allows anyone to enter a new order into the ChapR database. It is referenced
  // from weebly through iFrames (for both regular orders and the charity orders),
  // but it is also used on the orders.thechapr.com for admin to use. It uses orderForm.php
  // and customerForm.php to display the relevant fields, but those files both have
  // permission checks built in (so customers don't see admin-level info).
  // The form calls itself to validate user-entered information or enter orders.

include ("orderForm.php");
include ("customerForm.php");
include_once ("htmlFunctions.php");
include_once("DataBase/wordpress-helper-fns.php");

function showOrderEntryForm($data, $badFields = array())
{
  formHeader("","","orderEntry","void");

  customerFields($data, $badFields);
  orderFields($data, $badFields);
  
  if (inWordPress()){
    echo getWordpressHiddenFormField();
  }

  tableRow(array (tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(submit("Enter Order!"),"right")));

  formFooter("orderEntryForm");
}  

function main()
{
  // check if the form has been submitted (orderEntryForm is just a hidden field)
  if (!array_key_exists("orderEntryForm", $_GET)){
    showOrderEntryForm($_GET);
  }
  else {
    $badCustomerFields = customerValidate($_GET);
    $badOrderFields = orderValidate($_GET);
    $badFields = array_merge($badCustomerFields, $badOrderFields);
    if (count($badFields) != 0){
      showOrderEntryForm($_GET, $badFields);
    }
    else {
      $CID = addCustomerToDataBase($_GET);
      $OID = addOrderToDataBase($CID, $_GET);
      orderConfirmation($OID);
    }
  }
}

main();
?>