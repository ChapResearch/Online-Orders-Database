<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Customer Edit Custom Page
 *
 * @file           customer-edit.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php
include("../customerForm.php");
include_once ("../DataBase/dbFunctions.php");
include_once ("../DataBase/wordpress-helper-fns.php");
include_once("../DataBase/user.php");

function takeCustomerFromDataBase($CID)
{
  $dataBaseCustomer = dbGetCustomer($CID);

  $customer = array();

  $customer["CID"] = $CID;
  $customer["OID"] = $_GET["OID"]; // determines what page the form will return to
  $customer["fname"] = $dataBaseCustomer["FirstName"];
  $customer["lname"] = $dataBaseCustomer["LastName"];
  $customer["email"] = $dataBaseCustomer["Email"];
  $customer["title"] = $dataBaseCustomer["Title"];
  $customer["adminCNotes"] = $dataBaseCustomer["AdminCNotes"];
  $customer["street1"] = $dataBaseCustomer["Street1"];
  $customer["street2"] = $dataBaseCustomer["Street2"];
  $customer["city"] = $dataBaseCustomer["City"];
  $customer["state"] = $dataBaseCustomer["State"];
  $customer["zip"] = $dataBaseCustomer["Zip"];
  $customer["country"] = $dataBaseCustomer["Country"];
  $customer["customerCNotes"] = $dataBaseCustomer["CustomerCNotes"];
  $customer["metDate"] = date("m/d/Y", $dataBaseCustomer["MetDate"]);

  return $customer;
}

function main()
{
  if (array_key_exists("CID",$_GET)){
      $CID = $_GET["CID"];
  } else {
    print_r("ERROR: CID NOT FOUND");
    $CID = 1;
  }

  if (!array_key_exists("customerForm", $_GET)){
    $data = takeCustomerFromDataBase($CID);
    showCustomerForm($data, "", array()); // sends an empty array as the badFields
  }
  else {
      $badFields = customerValidate($_GET); // checks to make sure the info given is complete
      if (count($badFields) != 0){
	showCustomerForm($_GET, "", $badFields);
      }
      else {
	   // at this point we have good data, and just need to get it into the
	   // database.  Before we do it, get the OID from the form data so we
	   // know whether we need to go back to the orders page or customer page

	$OID = NULL;
	if(array_key_exists("OID",$_GET)) {
	     $OID = $_GET["OID"];
	}

	$customer = formatForDataBase($_GET);
	$CID = dbUpdate("customers", $customer, "CID", $CID);

	// get the configuration information from the Wordpress page so we know where
	// our target "return to" pages are

	$config = getConfigData();	
	$backToOrder =  getSpecialVariable("backToOrder",$config);
	$backToCustomer = getSpecialVariable("backToCustomer",$config);

	if($OID) { 		// came from order 
	  backToWPPage($backToOrder,"oid=$OID");
      	} else {		// otherwise from a customer edit
	  backToWPPage($backToCustomer,"CID=$CID");
	}
      }
  }
}

checkUserLoggedIn();
main();

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
