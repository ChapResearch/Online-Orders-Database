<?php

include('dbFunctions.php');

function populateListOrderSummary()
{
  $rows = dbGetOrdersSummary(); 
  $list = '

<head>
<style>
center td {align: center;
}
.topline {outline: thin solid black;}
tr:hover td {
  background-color: #FFFAF0; color: #000;
}
</style>
<script>
function rowClick(number) {
   alert("You clicked " + number);
}
</script>
</head>
<table frame="box" style="width:100%">
   <tr class="topline">
      <td align="center"> Customer ID </td>
      <td align="center"> Order ID </td>
      <td align="center"> First Name </td>
      <td align="center"> Last Name </td>
      <td align="center"> Ordered Date </td>
      <td align="center"> Item Count </td>
      <td align="center"> Paid? </td>
      <td align="center"> Shipped? </td>
      <td align="center"> Expedited? </td>
      <td align="center"> Order Notes </td>
   </tr>

';
  

  foreach($rows as $row) {

    $canceled = $row["WasCanceled"];
    $cid = $row["CID"];
    $oid = $row["OID"];
    $firstName = $row["FirstName"];
    $lastName = $row["LastName"];
    $orderDate = $row["OrderedDate"];
    $itemCount = $row["itemCount"];
    $paidDate = $row["PaidDate"];
    $shippedDate = $row["ShippedDate"];
    $isExpedited = $row["IsExpedited"];
    $orderNotes = $row["OrderNotes"];
    $paid = '&#9744;';  /* Empty Box special Character */
    $shipped = '&#9744;';  /* Empty Box special Character */
    $expedited = '&#9744;'; /* Empty Box special Character */

    if($paidDate == 0) {
      $paid = '&#9744;';  /* Empty Box special Character */
    } else {
      $paid = '&#9745;';  /* Check Box Special Character */
    }

    if($shippedDate == 0) {
      $shipped = '&#9744;';  /* Empty Box special Character */
    } else {
      $shipped = '&#9745;';  /* Check Box Special Character */
    }

    if($isExpedited) {
      $expedited = '&#9745;'; /* Check Box Special Character */
    } else {
      $expedited = '&#9744;'; /* Empty Box special Character */
    }

    if($canceled) {
      $list .= "<tr class=\"strikeout\">"; 
    } else {
      $list .= "<tr onclick=\"rowClick($oid);\">";
    }

    $list .= "

      <td align='center'> $cid </td>
      <td align='center'> $oid </td>
      <td> $firstName </td>
      <td> $lastName </td>
      <td class='center' align='center'> $orderDate </td>
      <td align='center'> $itemCount </td>
      <td align='center'> $paid </td>
      <td align='center'> $shipped </td>
      <td align='center'> $expedited </td>
      <td> $orderNotes </td>
   </tr>

";

  }

  $list .= " 

</table>
";

 print($list);

}



?>