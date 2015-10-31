<?php
include ("htmlFunctions.php");

echo '<head>
<style>
i  {color:red;}
</style>
</head>
<body>';

function showForm($err_msgs = null) 
{
  // generate error messages
  if ($err_msgs != null){
    foreach($err_msgs as $emsg){
      echo '<i>';
      echo "$emsg";
      echo ' </h4>';
    }
  }

echo '<form action="file2.php" method="get">
          <table class="table2" frame="border">';

 tableRow(array (tableData("right",prompt("<h1>Title!</h1>"))));

 tableRow(array (tableData("right",prompt("First name:")),
		 tableData("right",text("fname")),
		 tableData("right",prompt("Last name:")),
		 tableData("right",text("lname"))));

 tableRow(array (tableData("right",prompt("City:")),
		 tableData("right",text("city")),
		 tableData("right",prompt("State:")),
		 tableData("right",text("state"))));

 tableRow(array (tableData("right",prompt("<b>Gender:</b>")),
		 tableData("center",radioButton("gender", "male", false, "Male")),
		 tableData("center",radioButton("gender", "female", false, "Female")),
		 tableData("center",radioButton("gender", "other", true, "Other"))));

 tableRow(array (tableData("right",prompt("<b>Grade:</b>")),
		 tableData("center",radioButton("grade", "freshman", false, "9<sup>th</sup>")),
		 tableData("center",radioButton("grade", "sohpomore", false, "10<sup>th</sup>")),
		 tableData("center",radioButton("grade", "junior", false, "11<sup>th</sup>")),
		 tableData("center",radioButton("grade", "senior", false, "12<sup>th</sup>"))));

 tableRow(array (tableData("right",prompt("<b>Product:</b>")),
		 tableData("center",checkBox("product", "ChapR", false, "ChapR")),
		 tableData("center",checkBox("product", "Kit", false, "Kit")),
		 tableData("center",checkBox("product", "USB", false, "USB")),
		 tableData("center",checkBox("product", "Programmer", false, "Programmer"))));

 echo '</table>  <input type="hidden" name="filled" value="true"> </form>';
 /*    echo '

      <tr>
	<td align="right"><b>Product:</b></td>
p	<td colspan="3">
	  <table style="width:100%">
	    <tr>
	      <td align="center"><input type="checkbox" name="product" value="ChapR">ChapR</td>
	      <td align="center"><input type="checkbox" name="product" value="Programmer">Programmer</td>
	      <td align="center"><input type="checkbox" name="product" value="Kit">Kit</td>
	      <td align="center"><input type="checkbox" name="product" value="USB">USB</td>
	    </tr>
	  </table>
	</td>
      </tr>
      <tr>
	<td valign="top" align="right"><b>Comments:</b></td>
	<td colspan="3"><textarea rows="4" style="width:100%" name="comments">'; echo $_GET["comments"]; echo '</textarea></td>
      </tr>
      <tr>
	<td></td>
	<td></td>
	<td></td>
	<td align="right"><input type="submit" value="Submit!"></td>
      </tr>
    </table>
    <input type="hidden" name="filled" value="true">
    </form>';*/
}

function showPartialForm()
{
echo '
<form action="file2.php" method="get">

    <table class="table2" frame="border">
      <tr>
	<td><h1>Title</h1></td>
      </tr>
      <tr>
	<td align="right"><b>First Name:</b></td>
	 <td>';
echo $_GET["fname"];
echo '</td>
	<td align="right"><b>Last Name:</b></td>
         <td>';
echo $_GET["lname"];
echo '</td>
      </tr>
      <tr>
	<td align="right"><b>City: </b></td>
         <td>';
echo $_GET["city"];
echo '</td>
	 <td align="right"><b>State: </b></td>
          <td>';
echo $_GET["state"];
echo '</td>
      </tr>
      <tr>
	<td align="right"><b>Gender:</b></td>
         <td>';
echo $_GET["gender"];
echo '</td>
      </tr>
      <tr>
      <td align="right"><b>Grade:</b></td>
	<td align="right">';
echo $_GET["grade"];
echo '</tr>
</tr>
      <tr>
	<td align="right"><b>Product:</b></td>
	<td colspan="3">
	  <table style="width:100%">
	    <tr>
	      <td>';
echo $_GET["product"];
echo '
	    </tr>
	  </table>
	</td>
      </tr>
      <tr>
	<td valign="top" align="right"><b>Comments:</b></td>
        <td colspan="3">';
echo $_GET["comments"];
echo '	
      </tr>
      <tr>
	<td></td>
	<td></td>
	<td></td>
	<td align="right"><input type="submit" value="Submit!"></td>
      </tr>
    </table>
  </form>';
}

function formValidate()
{
  $retmsg = array();

  if (empty($_GET["fname"])){
    $retmsg[] = "Please fill in the first name!";
  }
  if (empty($_GET["lname"])){
    $retmsg[] = "Please fill in the last name!";
  }
  return ($retmsg);
}

?>
