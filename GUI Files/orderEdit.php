/*<?php
include("orderForm.php");

$repeatTimes = 5;

function takeOrderFromDataBase($OID)
{
  global $repeatTimes;

  $data = array();

  $order = dbGetOrder($OID);

  $data["orderNotes"] = $order["OrderNotes"];
  $data["isExpedited"] = $order["IsExpedited"];

  $items = dbGetItems($OID);

  foreach ($items as $item){
    $i = 1;
    $data["personality$i"] = $item["Personality"];
    $data["packages$i"] = $item["PKID"];
    $data["quantity$i"] = $item["Quantity"];
    $i++;
  }
  return $data;
}

function main($OID)
{
  global $repeatTimes;

  if (!array_key_exists("orderForm", $_GET)){
    $data = takeOrderFromDataBase($OID);
    showOrderForm($data, "orderEdit.php");
  }
  else {
    $err_msgs = orderValidate($repeatTimes);
    if (count($err_msgs) != 0){
      showOrderForm($_GET, "orderEdit.php", $err_msgs);
    }
    else {
      $customer = dbGetOrderCustomer($OID);
      print_r($customer);
      replaceOrderInDataBase($customer["CID"], $OID);
      orderConfirmation();
    }
  }
}

main(2);*/