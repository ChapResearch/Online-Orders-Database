<?php

function parseName($fullName)
{
  $retArray = explode(" ", $fullName);

  return $retArray;
}

function parseEmail($email)
{
  if (strpos($email, "@") !== false){
    return $email;
  }
  errorMsg("INVALID EMAIL");
  return $email;
}

function parseCustomerCNotes($notes)
{
  return $notes;
}

function parseAdminCNotes($notes)
{
  return "[AUTO INSERT] " . $notes;
}

function parseCustomerONotes($notes)
{
  return $notes;
}

function parseAdminONotes($notes)
{
  return $notes;
}

function parseProducts($products)
{
  $chaprBTAssembledPKID = 1;
  $chaprProgrammerPKID = 5;
  $chaprKitPKID = 3;

  // this pattern will match on our format for item identification
  //  including wild spaces thrown in accidentally

  $targetPattern = "/ *(.) *\( *([0-9][0-9]*) *\)/";

  $retItems = array();

  $items = explode(",", $products);

  foreach ($items as $item){

    if(!preg_match($targetPattern,$item,$matches)) {
	 errorMsg("didn't match on an item - Yikes!");
	 continue;
    }

    $keyLetter = $matches[1];
    $quantity = $matches[2];

    switch ($keyLetter){
    case "P" :
      $retItems[] = array("PKID" => $chaprProgrammerPKID, "Quantity" => $quantity, "Personality" => null, "Price" => null);
      $retItems[] = array("PKID" => $chaprBTAssembledPKID, "Quantity" => $quantity, "Personality" => null, "Price" => null);
      break;
    case "C" :
      $retItems[] = array("PKID" => $chaprBTAssembledPKID, "Quantity" => $quantity, "Personality" => null, "Price" => null);
      break;
    case "K" :
      $retItems[] = array("PKID" => $chaprKitPKID, "Quantity" => $quantity, "Personality" => null, "Price" => null);
      break;
    }
  }
  return($retItems);
}

function parseShipped($shipped, $default)
{
     if (parseYesNo($shipped,"Shipped")){ // if it has a yes
	  return strtotime($default);
     }
    return null;
}

function parseMetDate($metDate)
{
  return strtotime($metDate);
}

?>