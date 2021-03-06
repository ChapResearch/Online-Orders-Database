<?php

  // htmlFunctions.php
  //
  // a series of helper functions for generating html code. It currently is used to generate
  // ChapR forms and used primarily in customerForm.php and orderForm.php. The formHeader(),
  // tableRow(), hiddenField() and formFooter() are the only ones that print out html code.
  // The rest are designed to be used in nested arguments (though many arguments are optional 
  // and will be defaulted to empty strings once inside the function). For example,
  // 
  //        tableRow(array (tableData(prompt($promptText, $isRed), $leftRight),
  //                        tableData(text($data,$valueName), $leftRight, $topBottom)));
  //

//
// tableRow() - formats the given data inside the table row html tags and prints it
//
function tableRow($data,$id = "", $isDisplayed = true)
{
  print("<tr");
  if($id != "") {
       print(" id=\"$id\"");
  }
  if(!$isDisplayed){
    print(" style=\"display:none;\"");
  }
  print(">\n");

  foreach($data as $datum){
    print($datum);
  }
  print("</tr>\n\n");
}

//
// tableData() - given the data and possible alignments (left, center, right), possible
//               vertical alignments (bottom, center, top) and colspan (1 by default, int values),
//               returns the html for the table data, to be passed into the tableRow() function
//               as part of an array. NOTE: align and valign must be set to "" to change the colspan     
function tableData($data, $align = "",$valign = "",$colspan = "", $style = "")
{
  $retString = "<td class=\"form\"";
  $retString .= " style=\"";
  if ($align != ""){
    $retString .= " text-align:$align;";
  }
  if ($valign != ""){
    $retString .= " vertical-align:$valign;";
  }
  if ($style != ""){
    $retString .= " $style";
  }

  $retString .= "\"";
  if ($colspan != ""){
    $retString .= " colspan=\"$colspan\"";
  }
  $retString .= ">$data</td>\n";
  return $retString;
}

//
// tableHeader() - given the data and possible alignments (left, center, right), possible
//               vertical alignments (bottom, center, top) and colspan (1 by default, int values),
//               returns the html for the table header, to be passed into the tableRow() function
//               as part of an array. NOTE: align and valign must be set to "" to change the colspan     
function tableHeader($data, $align = "",$valign = "",$colspan = "")
{
  $retString = "<th";
  $retString .= " style=\"";
  if ($align != ""){
    $retString .= " text-align:$align;";
  }
  if ($valign != ""){
    $retString .= " vertical-align:$valign;";
  }
  $retString .= "\"";
  if ($colspan != ""){
    $retString .= " colspan=\"$colspan\"";
  }
  $retString .= ">$data</th>\n";
  return $retString;
}

//
// prompt() - paints the data label to the left of an input field
//
function prompt($prompt, $isRed = "", $id = "", $hoverText = "", $style = "")
{
  $retString = "<p id=\"$id\" style=";
  if ($isRed){
    $retString .= "\"color:red\"";
  } else if ($style != ""){
    $retString .= "\"$style\"";
  }
  $retString .= ">";
  if ($hoverText != ""){
    $retString .= "<span title=\"$hoverText\">$prompt</span>";
  }else{
    $retString .= "$prompt";
  }
  $retString .= "</p>";
  return($retString);
}

function prepDatePicker()
{
  echo "<link rel=\"stylesheet\" href=\"//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css\" />";
  echo '<script src="//code.jquery.com/jquery-1.10.2.js"></script>';
  echo '<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>';
  echo '<link rel="stylesheet" href="/resources/demos/style.css" />';
  echo '<script>';
  echo '$(function() { $( "#datepicker" ).datepicker();}); </script>';
}

//
// text() - paint a input field - if the given $data array
//		has the field in it, then use that as the
//		default value for the input field.
//
function text($data, $name, $default = "", $maxLength = "", $id = "")
{
  $retString = "<input type=\"text\" name=\"$name\" value=\"";

  if (array_key_exists($name, $data)){
    $retString .= "$data[$name]";
  }
  else if ($default != ""){
    $retString .= "$default";
  }

    $retString .= "\"";
  
  if ($maxLength != ""){
    $retString .= " size=\"$maxLength\"";;
  }

  if ($id != ""){
    $retString .= " id=\"$id\"";;
  }

  //  $retString .= " style=\"width:100%\">";
  $retString .= ">";
  return ($retString);
}

function radioButton($data, $name, $value, $default, $prompt)
{
  $retString = "<input type=\"radio\" name=\"$name\" value=\"$value\"";
  if ($data[$name] == $value || $default && empty($data["filled"])){
    $retString .= " checked=\"true\"";
  }
  $retString .= ">$prompt";
  return $retString;
}

function checkBox($data, $name, $value, $prompt, $default = false)
{
  $retString = "<input type=\"checkbox\" name=\"$name\" value=\"$value\"";
  if (array_key_exists($name,$data) && $data[$name] == $value || $default && empty($data["filled"])){
    $retString .= " checked=\"true\"";
  }
  $retString .= ">$prompt";
  return $retString;
}

function dropDown($data, $name, $options, $prompt = "")
{
  $retString = "<select name=\"$name\">\n";
  if ($prompt != ""){
    $retString .= "<option value=\"0\" selected=\"true\">$prompt</option>";
  }
  foreach ($options as $Name => $Value ){
    $retString .= "<option value=\"$Value\"";
    if (array_key_exists($name,$data) && $data[$name] == $Value && $data[$name] != 'x'){ #x meaning invalid 
      $retString .= " selected=\"true\"";
    }
    $retString .= ">$Name" . "</option>";
  }
  $retString .= "</select>";
  return $retString;
}

function button($prompt)
{
  return ("<button>$prompt</button>");
}

function textArea($data, $name, $rows)
{
  $retString = "<textarea rows=\"$rows\" name=\"$name\" style=\"width:100%; resize:none\">$data[$name]</textarea>";
  return $retString;
}

function formHeader($action, $name, $class, $frame)
{
  echo "<h1>$name</h1>";
  
  echo "<form action=\"$action\" class=\"$class\" method=\"get\">
          <table  frame=\"$frame\" class=\"form\">";
}

function formFooter($name)
{
  echo "</table>  <input type=\"hidden\" name=\"$name\" value=\"true\"> </form>";
}

function hiddenField($name, $value)
{
  echo "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
}

function submit($value)
{
  return ("<input type=\"submit\" value=\"$value\">");
}

?>