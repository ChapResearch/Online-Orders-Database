/*<?php
include("customerForm.php");
include_once ("DataBase/dbFunctions.php");

function takeCustomerFromDataBase($CID)
{
  $dataBaseCustomer = dbGetCustomer($CID);

  $customer = array();


  $customer[OID] = dbGetOrdersForCustomer($CID);
  $customer[fname] = $dataBaseCustomer[FirstName];
  $customer[lname] = $dataBaseCustomer[LastName];
  $customer[email] = $dataBaseCustomer[Email];
  $customer[phoneNum] = $dataBaseCustomer[Phone];
  $customer[title] = $dataBaseCustomer[Title];
  $customer[teamInfo] = $dataBaseCustomer[Teams];
  $customer[street1] = $dataBaseCustomer[Street1];
  $customer[street2] = $dataBaseCustomer[Street2];
  $customer[city] = $dataBaseCustomer[City];
  $customer[state] = $dataBaseCustomer[State];
  $customer[zip] = $dataBaseCustomer[Zip];
  $customer[country] = $dataBaseCustomer[Country];
  $customer[customerNotes] = $dataBaseCustomer[CustomerNotes];

  return $customer;
}

function main()
{
  print_r($_GET);
  if (array_key_exists("cid",$_GET)){
      $CID = $_GET["cid"];
      print_r($CID);
  } else {
    $CID = 1;
  }
  if (!array_key_exists("customerForm", $_GET)){
    $data = takeCustomerFromDataBase($CID);
    showCustomerForm($data, "customerEdit.php");
  }
  else {
    if ($_GET["customerForm"] == "true"){
    $err_msgs1 = customerValidate();
 #   $err_msgs = customerValidate();
 /*   $err_msgs2 = orderValidate($repeatTimes);
    $err_msgs = array_merge($err_msgs1, $err_msgs2);

      //      $err_msgs = customerValidate();
      if (count($err_msgs) != 0){
	showCustomerForm($_GET, "customerEdit.php", $err_msgs);
      }
      else {
	print_r(replaceCustomerInDataBase());
	showConfirmation();
      }
    }
  }
}

main();*/