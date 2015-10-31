<?php

  // packageList.php 
  // 
  // contains methods for filling the table under "Manage Packages". The
  // populateListPackagesSummary() method is meant to be called by the WordPress
  // page. It pulls the most relevant package data from the database, formats
  // them into rows and gives sorting capabilities (by headers), as well as editing
  // capabilities (by clicking on the row).

include('dbFunctions.php');


//
// showAllButton() - much like showHeader() compose a piece of HTML for
//		     use within the order table to allow the user to
//		     click to include/exclude canceled and shipped orders.
//
function showAllButton($basepage,$sort,$reverse,$all)
{
     $retVal  = "<tr class=\"botline\"><td colspan=13 align=\"right\">\n";
     $retVal .= "<a href=\"$basepage&sort=$sort";

     if($reverse) {
	  $retVal .= "&reverse";
     }

     if(!$all) {
	  $retVal .= "&all";
     }

     $retVal .= "\"><em>Click here to ";
     if($all) {
	  $retVal .= "exclude";
     } else {
	  $retVal .= "include";
     }

     $retVal .= " nonactive packages.</em></a></td></tr>";

     return($retVal);
}

//
// addNewButton() - much like showHeader() compose a piece of HTML for
//		     use within the order table to allow the user to
//		     add a new package.
//
function addNewButton($basepage)
{
     $retVal  = "<tr class=\"botline\"><td colspan=13 align=\"right\">\n";
     $retVal .= "<a href=\"$basepage&sort=$sort";

     if($reverse) {
	  $retVal .= "&reverse";
     }

     if(!$all) {
	  $retVal .= "&all";
     }

     $retVal .= "\"><em>Click here to add a new package";

     $retVal .= " </em></a></td></tr>";

     return($retVal);
}

//
// sortHeader() - a little helper function to format a header with sorting.
//
function sortHeader($basepage,$label,$field,$fieldNow,$reverseNow,$all)
{
     $retVal = "<a href=\"$basepage&sort=$field";
  
     if($all) {
       $retVal .= "&all";
     }

     if($fieldNow == $field && !$reverseNow) {
	  $retVal .= "&reverse";
     }
     if($fieldNow == $field) {
	  $retVal .= "\"><strong>$label</strong></a>";
     } else {
	  $retVal .= "\">$label</a>";
     }

     return($retVal);
}

function populateListPackagesSummary($linkPage, $sort = "PKID",$reverse = false, $all)
{

  // change these if Wordpress location changes
  $base = "http://orders.thechapr.com/Admin";
  
  $basepage = "?page_id=" . $_GET["page_id"];

  $rows = dbGetPackagesSummary($sort,$reverse, $all); 

  $list = "";

  $list .= '<script>
               // code taken from http://stackoverflow.com/questions/133925/javascript-post-request-like-a-form-submit
               function post(path, params, method) {
                  method = method || "post"; // Set method to post by default if not specified.

                  var form = document.createElement("form");
                  form.setAttribute("method", method);
                  form.setAttribute("action", path);

                  for(var key in params) {
                     if(params.hasOwnProperty(key)) {
                        var hiddenField = document.createElement("input");
                        hiddenField.setAttribute("type", "hidden");
                        hiddenField.setAttribute("name", key);
                        hiddenField.setAttribute("value", params[key]);
 
                        form.appendChild(hiddenField);
                     }
                  }

                  document.body.appendChild(form);
                  form.submit();
               }';
  $list .= "
               function rowClick(PKID) {
                  window.location.href = \"$linkPage\" + \"&PKID=\" + PKID;
               }
               </script>";
     
     $list .= "
<table class=\"packageList\" frame=\"box\" style=\"width:95%;align:center\">";
     $list .= addNewButton($basepage,$sort,$reverse,$all);
     $list .= showAllButton($basepage,$sort,$reverse,$all);
     $list .= "
   <tr class=\"topline\">
      <td align=\"center\"><font size=\"-2\">" . sortHeader($basepage,"PKID","PKID",$sort,$reverse,$all) . "</td>
      <td align=\"center\">" . sortHeader($basepage,"Package Name","PackageName",$sort,$reverse,$all) . "</td>
      <td align=\"center\">" . sortHeader($basepage,"Price","Price",$sort,$reverse, $all) . "</td>
      <td align=\"center\">" . sortHeader($basepage,"Active?","Active",$sort,$reverse, $all) . "</td>
   </tr>

";

     foreach($rows as $row) {
       $PKID = $row["PKID"];
       $name = $row["PackageName"];
       $price = $row["Price"];
       if ($row["Active"] == 1){
	 $active = "YES";
       } else {
	 $active = "NO";
       }
       
       $list .= "<tr onclick=\"rowClick($PKID);\">";
       
       $list .= "
      <td class='centered' align='center'> $PKID </td>
      <td align='center'> $name </td>
      <td align='center'> $price </td>
      <td align='center'> $active </td>
      </tr>
      ";
     }
     
  $list .= " 

</table>
<div align=\"center\"><font size=\"-1\"><em>
Click on a row to view or edit the package.
</em></font></div>
";

 print($list);

}

?>