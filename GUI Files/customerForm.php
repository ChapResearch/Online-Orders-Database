<?php
include_once ("htmlFunctions.php");
include_once ("DataBase/dbFunctions.php");
include_once ("DataBase/wordpress-helper-fns.php");
include_once ("states.php");
include_once ("DataBase/user.php");
include_once ("DataBase/countryCodes.php");

function customerFields($data, $badFields)
{
  global $states;
  global $COUNTRIES;

  if (userLoggedIn() && array_key_exists("CID", $data) && $data["CID"] != ""){
    tableRow(array (tableData(prompt("<b>CID:</b>"), "right", "top"),
		    tableData(prompt($data["CID"]), "left", "top")));
    prepDatePicker();
    tableRow(array (tableData(prompt("<b>Met Date:</b>"), "right", "top"),
		    tableData(text($data,"metDate", "", "", "datepicker"), "left", "middle")));
  }
  
  tableRow(array (tableData(prompt("<b>First name*:</b>", in_array("fname", $badFields)), "right"),
		  tableData(text($data,"fname"), "left", "middle"),
		  tableData(prompt("<b>Last name*:</b>", in_array("lname", $badFields)), "right"),
		  tableData(text($data,"lname"), "left", "middle")));

  tableRow(array (tableData(prompt("<b>Email*:</b>",  in_array("email", $badFields)), "right"),
		  tableData(text($data,"email"), "left", "middle"),
		  tableData(prompt("<b>Phone Number:</b>", in_array("phoneNum", $badFields)), "right"),
		  tableData(text($data,"phoneNum"), "left", "middle")));
  
  tableRow(array (tableData(prompt("<b>Title:</b>", in_array("title", $badFields)), "right"),
		  tableData(radioButton($data,"title", "Mr.", false, "Mr."), "center", "middle"),
		  tableData(radioButton($data,"title", "Ms.", false, "Ms."), "center", "middle"),
		  tableData(radioButton($data,"title", "Mrs.", false, "Mrs."), "center", "middle"),
		  tableData(radioButton($data,"title", "Dr.", false, "Dr."), "center", "middle")));

  if (!userLoggedIn()){
    if ($data["charity"] == true){
	tableRow(array (tableData(prompt("Tell us about your situtation, your team and why you need a ChapR. The more information the better!"), "middle", "top", 6)));
      } else {
	tableRow(array (tableData(prompt("Write anything else you would like us to know about you below: <br> team info (type, name, number), how you heard about us (where, from who?) etc."), "middle", "top", 6)));
      }
  }

  tableRow(array (tableData(prompt("<b>Comments:</b>", in_array("customerCNotes", $badFields), ""), "right", "top"),
		  tableData(textArea($data,"customerCNotes", 3), "center", "", 5)));

  tableRow(array (tableData(prompt("<b>Street1*:</b>", in_array("street1", $badFields)),"right"),
		  tableData(text($data,"street1"), "left", "middle", 3)));
  
  tableRow(array (tableData(prompt("<b>Street2:</b>", in_array("street2", $badFields)), "right"),
		  tableData(text($data,"street2"), "left", "middle", 3)));

  $stateDirections = 'only applicable for domestic teams';

  tableRow(array (tableData(prompt("<b>City*:</b>", in_array("city", $badFields)), "right"),
		  tableData(text($data,"city"), "left", "middle"),
		  tableData(prompt("<b>State*:</b>", in_array("state", $badFields), "", $stateDirections), "right"),
		  tableData(dropDown($data,"state", $states, "--------Choose Your State-------"), "left", "middle")));
  
  tableRow(array (tableData(prompt("<b>Zip*:</b>", in_array("zip", $badFields)), "right"),
		  tableData(text($data,"zip"), "left", "middle"),
		  tableData(prompt("<b>Country:</b>", in_array("country", $badFields)), "right"),
		  tableData(dropDown($data,"country", $COUNTRIES), "left", "middle")));

  if (userLoggedIn()){
    tableRow(array (tableData(prompt("<b>Admin Comments:</b>", in_array("adminCNotes", $badFields), "",
				     $commentDirections), "right", "top"),
		    tableData(textArea($data,"adminCNotes", 3), "center", "", 5)));
  }

  tableRow(array( tableData(hiddenField("CID", $data["CID"])),
		  tableData(hiddenField("OID", $data["OID"]))));

}

function showCustomerForm($data, $action, $badFields)
{
  formHeader($action, "<h1>Customer Info Form</h1>", "customerForm", "void");

  customerFields($data, $badFields);

  tableRow(array (tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(submit("Enter/Edit Customer!"),"right")));

  echo(getWordpressHiddenFormField());

  formFooter("customerForm");
}


function customerValidate($data)
{
  $badFields = array();

  if (empty($data["fname"])){
    $badFields[] = "fname";
  }
  if (empty($data["lname"])){
    $badFields[] = "lname";
  }
  if (empty($data["email"])){
    $badFields[] = "email";
  }
  if (empty($data["street1"])){
    $badFields[] = "street1";
  }
  if (empty($data["city"])){
    $badFields[] = "city";
  }
  if (empty($data["state"]) || $data["state"] == "x"){ #x here meaning an invalid answer
    $badFields[] = "state";
  }
  if (empty($data["zip"])){
    $badFields[] = "zip";
  }
  return ($badFields);
}

function formatForDataBase($data)
{
  $customer = array();

  $customer["CID"] = $data["CID"];
  $customer["FirstName"] = $data["fname"];
  $customer["LastName"] = $data["lname"];
  $customer["Email"] = $data["email"];
  $customer["Title"] = $data["title"];
  $customer["Phone"] = $data["phoneNum"];
  $customer["Title"] = $data["title"];
  $customer["CustomerCNotes"] = $data["customerCNotes"];
  $customer["AdminCNotes"] = $data["adminCNotes"];
  if (array_key_exists("metDate", $data) && $data["metDate"] != ""){
    $customer["MetDate"] = strtotime($data["metDate"]);
  } else {
    $customer["MetDate"] = time();
  }
  $customer["Street1"] = $data["street1"];
  $customer["Street2"] = $data["street2"];
  $customer["City"] = $data["city"];
  $customer["State"] = $data["state"];
  $customer["Zip"] = $data["zip"];
  $customer["Country"] = $data["country"];

  return $customer;
}

// TODO - pull out formatForDataBase()
function addCustomerToDataBase($data)
{
  $CID = dbInsertNewCustomer(formatForDataBase($data));

  return $CID;
}

