<?php

function showFormData()
{
  echo '<table frame="box">
	 <tr>
	    <td align="right"> <b>First Name:</b> </td> <td> <b>'; echo	 $_GET["firstname"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';	 
echo '	    <td align="right"> <b>Last Name:</b> </td> <td> <b>'; echo	$_GET["lastname"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>City:</b> </td> <td> <b>'; echo  $_GET["city"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>State:</b> </td> <td> <b>'; echo  $_GET["state"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Gender:</b> </td> <td> <b>'; echo  $_GET["group1"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Grade:</b> </td> <td> <b>'; echo  $_GET["group2"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Chapr:</b> </td> <td> <b>'; echo  $_GET["chapr"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Programmer:</b> </td> <td> <b>'; echo	 $_GET["prog"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Kit:</b> </td> <td> <b>'; echo  $_GET["kit"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Cat:</b> </td> <td> <b>'; echo  $_GET["cat"]; echo '</b> </td>';
echo '	 </tr>';
echo '	 <tr>';
echo '	    <td align="right"> <b>Comments:</b> </td> <td> <b>'; echo  $_GET["comments"]; echo '</b> </td>';
echo '	 </tr>';
echo '</table>';
}

function formBuilder($data)
{


}

function formFilter($data)
{
  return(htmlspecialchars($data));
}

function tableData($data)
{
  print("<td>$data</td>\n");
}

function radioButton($group,$value,$checked,$prompt) 
{
  $retString = "";
  $retString .= "<input type=\"radio\" name=\"$group\" value=\"$value\" ";
  if($checked) {
    $retString .= "checked=\"checked\"";
  }
  $retString .= ">";
  $retString .= $prompt;
  return($retString);
}

function showForm($errormsg = null)
{
  echo '<head>
          <style>
            i  {color: red;}
          </style>
        </head>
        <body>
  ';
  if($errormsg != null){
    foreach ($errormsg as $emsg){
      echo "<i> $emsg </i><br>";
    }
  }

echo '
<form action="file1.php" class="form1">
<input type="hidden" name="filled" value="true">
   <table frame="box">
      <tr>
         <td><h1>Title</h1></td>
      </tr>
      <tr>
         <td align="right"> <b>First Name:</b> </td> <td> <input type="text" value="'; echo formFilter($_GET['firstname']); echo '" name="firstname"></td>
         <td align="right"> <b>Last Name:</b> </td> <td> <input type="text" value="'; echo formFilter($_GET['lastname']); echo '" name="lastname"></td>
      </tr>
      <tr>
         <td align="right"> <b>City:</b> </td> <td> <input type="text" value="'; echo formFilter($_GET['city']); echo '" name="city"></td>
         <td align="right"> <b>State:</b> </td> <td> <input type="text" value="'; echo formFilter($_GET['state']); echo '" name="state"></td>
      </tr>
      <tr>
         <td align="right"> <b>Gender:</b> </td>
         <td colspan="3">
            <table align="center" width="100%">
              <tr>';

 tableData(radioButton("group1","Male",$_GET["group1"] == "Male","Male"));
 tableData(radioButton("group1","Female",$_GET["group1"] == "Female","Female"));
 tableData(radioButton("group1","Other",$_GET["group1"] == "Other","Other"));

 echo '
              </tr>
            </table>
         </td>
      </tr>
      <tr>
         <td align="right"> <b>Grade:</b> </td>
         <td colspan="3">
            <table align="center" width="100%">
              <tr>';

 tableData(radioButton("group2","Nine",$_GET["group2"] == "Nine","9<sup>th</sup>"));
 tableData(radioButton("group2","Ten",$_GET["group2"] == "Ten","10<sup>th</sup>"));
 tableData(radioButton("group2","Eleven",$_GET["group2"] == "Eleven","11<sup>th</sup>"));
 tableData(radioButton("group2","Twelve",$_GET["group2"] == "Twelve","12<sup>th</sup>"));

  
echo '

              </tr>
            </table>
         </td>
      </tr>
      <tr>
         <td align="right"> <b>Products:</b> </td>
         <td colspan="3">
            <table align="center" width="100%">
              <tr>
                 <td> <input type="checkbox" name="chapr" value="$100"> Chapr </td>
                 <td> <input type="checkbox" name="prog" value="$50"> Programmer </td>
                 <td> <input type="checkbox" name="kit" value="$75"> Kit </td>
                 <td> <input type="checkbox" name="cat" value="$100000"> Cat </td>
              </tr>
            </table>
         </td>
      </tr>
      <tr>
         <td align="right" valign="top"> <b>Comments:</b> </td> <td colspan="3"> <textarea  name="comments" style="width: 100%;" rows="4" cols="50">';
   echo formFilter($_GET['comments']); echo '</textarea></td>
      </tr>

      <tr>
         <td></td>
         <td></td>
         <td></td>
      <td align="right"><input type="submit" value="Submit"></td>
      </tr>
   </table>
</form>
</body>
';
}

function formValidate()
{
  $retmsg = array();

  if($_GET["firstname"] == ""){
    $retmsg[] = "You must fill in your first name.";
  }
  if($_GET["lastname"] == ""){
    $retmsg[] = "You must fill in your last name.";
  }
  if($_GET["city"] == ""){
    $retmsg[] = "You must fill in your city.";
  }
  if($_GET["state"] == ""){
    $retmsg[] = "You must fill in your state.";
  }
  if($_GET["comments"] == ""){
    $retmsg[] = "You must fill in a comment.";
  }
  return($retmsg);
}

?>
