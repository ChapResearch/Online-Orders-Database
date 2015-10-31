<?php
include_once ("htmlFunctions.php");

function packageFields($data, $badFields)
{
  print_r("data");
  print_r($data);
  tableRow(array (tableData(prompt("<b>Package Name:</b>", in_array("packname", $badFields)), "right"),
		  tableData(text($data,"packname", "", "30"), "left", "middle"),
		  tableData(prompt("<b>Package Price:</b>", in_array("packprice", $badFields)), "right"),
		  tableData(text($data,"packprice", "", "10"), "left", "middle"),
		  tableData(prompt("<b>Active?</b>", in_array("active", $badFields)), "right"),
		  tableData(checkBox($data,"active","false","YES"), "left", "middle", 3)));

  $pieces = $data["pieces"]; // the list of all the pieces included in the package (as pulled from the pvp table)

  $i = 1;

  foreach ($pieces as $piece){
    $pieceInfo = dbGetPiece($piece["PID"]); // get the info for the piece with that PID
    $pieceInfo["PID$i"] = $pieceInfo["PID"];
    $piecesOptions = dbGetPiecesNames();
    print_r("piecesOptions");
    print_r($piecesOptions);
    tableRow(array (tableData(prompt("<b>Piece$i:</b>", in_array("piece$i", $badFields)), "right"),
		    tableData(dropDown($pieceInfo, "PID$i", $piecesOptions))));
    $i++;
  }


  hiddenField("PKID",$data["PKID"]);
  print("\n\n");
}

function showPackageForm($data, $action, $badFields)
{
  formHeader($action, "Package Form", "packageForm", "void");
  packageFields($data, $badFields);
  tableRow(array (tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(""),
		  tableData(submit("Submit!"))));

  echo(getWordpressHiddenFormField());

  formFooter("packageForm");
}  

function formatForDataBase($data)
{
  $dbPackage["PKID"] = $data["PKID"];
  $dbPackage["PackageName"] = $data["packname"];
  $dbPackage["Price"] = $data["packprice"];
  $dbPackage["Active"] = false; // creates the behavior that a record will be kept as history when edited
}


function packageValidate($data)
{
  $badFields = array();

  if (empty($data["packname"])){
    $badFields[] = "packname";
  }
  if (empty($data["packprice"]) || !is_numeric($data["packprice"])){
    $badFields[] = "packprice";
  }
  if (empty($data["active"])){
    $badFields[] = "active";
  }

  return $badFields;
}
?>